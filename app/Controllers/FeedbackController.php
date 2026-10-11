<?php

namespace App\Controllers;

use App\Models\FeatureVoteModel;
use App\Models\TestimonialModel;
use CodeIgniter\HTTP\ResponseInterface;

class FeedbackController extends BaseController
{
    /**
     * Endpoint AJAX untuk mencatat voting kepuasan fitur tertentu (+1 Membantu / -1 Kurang Pas)
     */
    public function vote(): ResponseInterface
    {
        $request = $this->request;
        $reportId = (int) $request->getPost('report_id');
        $featureName = trim((string) $request->getPost('feature_name'));
        $voteValue = (int) $request->getPost('vote_value');
        $feedbackText = $request->getPost('feedback_text');

        if (empty($featureName)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parameter nama fitur wajib diisi.',
            ])->setStatusCode(400);
        }

        // Batasi pilihan vote hanya +1 atau -1
        $voteValue = ($voteValue >= 1) ? 1 : -1;

        $userId = session()->get('user_id') ? (int) session()->get('user_id') : null;
        $ip = $request->getIPAddress();

        $voteModel = new FeatureVoteModel();
        $result = $voteModel->recordVote(
            $reportId ?: null,
            $featureName,
            $voteValue,
            $userId,
            $feedbackText ? (string) $feedbackText : null,
            $ip
        );

        $msg = ($voteValue === 1) 
            ? 'Terima kasih atas apresiasi Anda! Masukan ini sangat berharga bagi peningkatan engine AR Tilik.' 
            : 'Terima kasih atas masukan kritis Anda. Tim kami akan terus menyempurnakan akurasi fitur ini.';

        return $this->response->setJSON([
            'success'    => true,
            'message'    => $msg,
            'action'     => $result['action'],
            'vote_value' => $result['vote_value'],
        ]);
    }

    /**
     * Endpoint AJAX untuk mengirim rating keseluruhan naskah (1-5 bintang) & testimoni
     */
    public function submitTestimonial(): ResponseInterface
    {
        $request = $this->request;
        $reportId = (int) $request->getPost('report_id');
        $rating = max(1, min(5, (int) ($request->getPost('rating') ?? 5)));
        $content = trim((string) ($request->getPost('content') ?? ''));
        
        $session = session();
        $userId = $session->get('user_id') ? (int) $session->get('user_id') : null;
        $sessionName = $session->get('user_name');
        
        $authorName = trim((string) ($request->getPost('author_name') ?? $sessionName ?? 'Akademisi AR Tilik'));
        $authorRole = trim((string) ($request->getPost('author_role') ?? 'Mahasiswa'));
        $institution = trim((string) ($request->getPost('institution') ?? ''));
        $isAnonymous = !empty($request->getPost('is_anonymous')) ? 1 : 0;
        $allowPublic = !empty($request->getPost('allow_public_display')) ? 1 : 0;

        if (empty($content) && $rating >= 4) {
            // Jika bintang 4 atau 5 tanpa isi, isi teks apresiasi default
            $content = 'Laporan pemeriksaan similarity dan audit forensik AR Tilik sangat membantu mempersiapkan naskah akademik saya.';
        } elseif (empty($content) && $rating < 4) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Mohon berikan sedikit catatan perbaikan agar kami dapat meningkatkan kualitas laporan.',
            ])->setStatusCode(400);
        }

        $testimonialModel = new TestimonialModel();
        $result = $testimonialModel->submitReview([
            'report_id'            => $reportId ?: null,
            'user_id'              => $userId,
            'rating'               => $rating,
            'content'              => $content,
            'author_name'          => $authorName,
            'author_role'          => $authorRole,
            'institution'          => !empty($institution) ? $institution : null,
            'is_anonymous'         => $isAnonymous,
            'allow_public_display' => $allowPublic,
            'ip_address'           => $request->getIPAddress(),
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Terima kasih banyak! Ulasan dan penilaian Anda berhasil disimpan.',
            'rating'  => $result['rating'],
            'action'  => $result['action'],
        ]);
    }

    /**
     * Mengambil status ulasan dan voting pengguna untuk satu laporan
     */
    public function getStatus(int $reportId): ResponseInterface
    {
        $userId = session()->get('user_id') ? (int) session()->get('user_id') : null;
        $ip = $this->request->getIPAddress();

        $voteModel = new FeatureVoteModel();
        $testimonialModel = new TestimonialModel();

        $votes = $voteModel->getReportVotes($reportId, $userId, $ip);
        $review = $testimonialModel->getReviewForReport($reportId, $userId, $ip);

        return $this->response->setJSON([
            'success' => true,
            'votes'   => $votes,
            'review'  => $review,
        ]);
    }
}
