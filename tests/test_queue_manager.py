"""
Unit and Integration Tests for AR Tilik Redis Event-Driven Task Queue (Fase 1 - v1.4)
Memverifikasi:
1. Prioritas Dual-Queue (Fast vs Heavy segregation)
2. LPUSH / BRPOP FIFO behaviour & strict fast-lane prioritization
3. Dead-Letter Queue (DLQ) isolation
4. Queue Metrics observability
5. PHP QueueService TCP stream dispatcher integration
"""

import os
import sys
import json
import subprocess
import unittest

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from worker.queue_manager import (
    get_redis_client,
    is_redis_available,
    determine_queue_priority,
    enqueue_job,
    pop_next_job,
    push_to_dead_letter,
    get_queue_metrics,
    QUEUE_FAST,
    QUEUE_HEAVY,
    QUEUE_DLQ
)


TEST_QUEUE_FAST = "artilik:queue:test_fast"
TEST_QUEUE_HEAVY = "artilik:queue:test_heavy"
TEST_QUEUE_PHP = "artilik:queue:test_php"


class TestQueueManager(unittest.TestCase):

    def setUp(self):
        self.redis = get_redis_client()
        if self.redis:
            self.redis.delete(TEST_QUEUE_FAST, TEST_QUEUE_HEAVY, TEST_QUEUE_PHP, QUEUE_DLQ)

    def tearDown(self):
        if self.redis:
            self.redis.delete(TEST_QUEUE_FAST, TEST_QUEUE_HEAVY, TEST_QUEUE_PHP, QUEUE_DLQ)

    def test_01_redis_liveness(self):
        """Memastikan koneksi Redis aktif atau terdeteksi liveness-nya."""
        avail = is_redis_available()
        self.assertTrue(avail, "Redis server harus dalam keadaan aktif di port 6379")

    def test_02_priority_segregation(self):
        """Memverifikasi penentuan antrean berdasarkan jenis job dan muatan komputasi."""
        # Fast jobs
        self.assertEqual(determine_queue_priority("index_corpus", {}), QUEUE_FAST)
        self.assertEqual(determine_queue_priority("reindex", {}), QUEUE_FAST)
        self.assertEqual(determine_queue_priority("notify", {}), QUEUE_FAST)
        self.assertEqual(determine_queue_priority("similarity_check", {"engine": "exact"}), QUEUE_FAST)
        self.assertEqual(determine_queue_priority("similarity_check", {"engine": "idf"}), QUEUE_FAST)

        # Heavy jobs
        self.assertEqual(determine_queue_priority("similarity_check", {"engine": "hybrid"}), QUEUE_HEAVY)
        self.assertEqual(determine_queue_priority("similarity_check", {}), QUEUE_HEAVY)
        self.assertEqual(determine_queue_priority("deep_forensic_audit", {}), QUEUE_HEAVY)

    def test_03_enqueue_and_fifo_order(self):
        """Memverifikasi LPUSH dan BRPOP menghasilkan urutan FIFO yang tepat."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        # Masukkan 2 job ke antrean fast khusus testing
        enqueue_job(101, "index_corpus", {"document_id": 1}, priority=TEST_QUEUE_FAST)
        enqueue_job(102, "index_corpus", {"document_id": 2}, priority=TEST_QUEUE_FAST)

        q1, job1 = pop_next_job(timeout=1, queues=[TEST_QUEUE_FAST])
        self.assertEqual(q1, TEST_QUEUE_FAST)
        self.assertEqual(job1["id"], 101)

        q2, job2 = pop_next_job(timeout=1, queues=[TEST_QUEUE_FAST])
        self.assertEqual(q2, TEST_QUEUE_FAST)
        self.assertEqual(job2["id"], 102)

    def test_04_fast_lane_priority_over_heavy(self):
        """Memverifikasi bahwa pop_next_job mendahulukan FAST lane meskipun HEAVY masuk lebih dulu."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        # Heavy job masuk duluan
        enqueue_job(201, "similarity_check", {"engine": "hybrid"}, priority=TEST_QUEUE_HEAVY)
        # Fast job masuk belakangan
        enqueue_job(202, "index_corpus", {"document_id": 99}, priority=TEST_QUEUE_FAST)

        # Pop pertama HARUS mengambil Fast job (202)
        q1, job1 = pop_next_job(timeout=1, queues=[TEST_QUEUE_FAST, TEST_QUEUE_HEAVY])
        self.assertEqual(q1, TEST_QUEUE_FAST)
        self.assertEqual(job1["id"], 202)

        # Pop kedua baru mengambil Heavy job (201)
        q2, job2 = pop_next_job(timeout=1, queues=[TEST_QUEUE_FAST, TEST_QUEUE_HEAVY])
        self.assertEqual(q2, TEST_QUEUE_HEAVY)
        self.assertEqual(job2["id"], 201)

    def test_05_dead_letter_queue_isolation(self):
        """Memverifikasi tugas yang gagal fatal dipindahkan ke Dead-Letter Queue."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        bad_job = {"id": 666, "type": "corrupted_job", "payload": {}}
        push_to_dead_letter(bad_job, "SyntaxError on parsing PDF")

        metrics = get_queue_metrics()
        self.assertEqual(metrics["dlq_count"], 1)

        # DLQ tidak boleh di-pop oleh antrean reguler
        res = pop_next_job(timeout=1, queues=[TEST_QUEUE_FAST, TEST_QUEUE_HEAVY])
        self.assertIsNone(res, "Worker reguler tidak boleh mem-pop dari Dead-Letter Queue")

    def test_06_php_queueservice_dispatch_integration(self):
        """Memverifikasi bahwa PHP QueueService::dispatch berhasil mengirim tugas ke Redis dan MariaDB."""
        if not self.redis:
            self.skipTest("Redis tidak tersedia")

        php_script = r"""
        require_once 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
        $jobId = \App\Libraries\QueueService::dispatch('similarity_check', [
            'document_id' => 8888,
            'report_id' => 9999,
            'engine' => 'hybrid'
        ], 'artilik:queue:test_php');
        echo json_encode(['job_id' => $jobId]);
        """

        proc = subprocess.run(
            ["php", "-r", php_script],
            capture_output=True,
            text=True,
            cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
        )
        self.assertEqual(proc.returncode, 0, f"PHP error: {proc.stderr}")
        data = json.loads(proc.stdout)
        job_id = data.get("job_id")
        self.assertGreater(job_id, 0)

        # Cek apakah job sampai di Redis TEST_QUEUE_PHP
        q, job_data = pop_next_job(timeout=2, queues=[TEST_QUEUE_PHP])
        self.assertEqual(q, TEST_QUEUE_PHP)
        self.assertEqual(job_data["id"], job_id)
        self.assertEqual(job_data["payload"]["document_id"], 8888)


if __name__ == "__main__":
    unittest.main()
