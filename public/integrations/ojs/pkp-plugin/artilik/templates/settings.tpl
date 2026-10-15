{**
 * plugins/generic/artilik/templates/settings.tpl
 *
 * Formulir Pengaturan Plugin AR Tilik untuk OJS 3.x
 *}
<script>
    $(function() {ldelim}
        $('#artilikSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<form class="pkp_form" id="artilikSettingsForm" method="post" action="{url op="manage" category="generic" plugin=$pluginName verb="settings" save="true"}">
    {csrf}
    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="artilikSettingsNotification"}

    <div class="pkp_form_area">
        <p class="description" style="margin-bottom: 1.5em; line-height: 1.5; color: #475569;">
            Hubungkan jurnal Anda ke mesin evaluasi orisinalitas <strong>Dual-Lens</strong> (5-Gram Exact &amp; TF-IDF Cosine Vector) serta <strong>Audit Forensik Cerdas AI</strong> AR Tilik.
            Dapatkan API Key melalui portal <a href="https://tilik.arsetontong.top" target="_blank" style="color: #0284c7; text-decoration: underline;">https://tilik.arsetontong.top</a> (Menu Integrasi API &amp; OJS).
        </p>

        {fbvFormArea id="artilikSettingsArea"}
            {fbvFormSection title="API Key AR Tilik" required="true"}
                {fbvElement type="text" id="apiKey" name="apiKey" value=$apiKey maxlength="128" required="true"}
                <span class="instruct" style="font-size: 0.85em; color: #64748b; display: block; margin-top: 4px;">
                    Format kunci autentikasi: <code>artilik_ojs_...</code> (Dibuat di dashboard AR Tilik)
                </span>
            {/fbvFormSection}

            {fbvFormSection title="API Endpoint URL"}
                {fbvElement type="text" id="apiEndpoint" name="apiEndpoint" value=$apiEndpoint maxlength="255"}
                <span class="instruct" style="font-size: 0.85em; color: #64748b; display: block; margin-top: 4px;">
                    Default: <code>https://tilik.arsetontong.top/api/v1</code> (Gunakan default kecuali menggunakan instance lokal/private)
                </span>
            {/fbvFormSection}

            {fbvFormSection title="Opsi Otomasi Alur Kerja"}
                {fbvElement type="checkbox" id="autoCheckOnDecision" name="autoCheckOnDecision" value="1" checked=$autoCheckOnDecision label="Jalankan evaluasi naskah otomatis saat Editor merekam keputusan (Review/Revisi)"}
                <span class="instruct" style="font-size: 0.85em; color: #64748b; display: block; margin-top: 4px;">
                    Pengecekan menggunakan <strong>Mode Polling Outbound</strong> sehingga tidak memerlukan IP publik, port forwarding, atau tunnel inbound.
                </span>
            {/fbvFormSection}
        {/fbvFormArea}

        {fbvFormButtons submitText="common.save"}
    </div>
</form>
