<style>
    /* Company Header Styles */
    .ch-container {
        display: flex; align-items: center; justify-content: space-between;
        padding-bottom: 12px; margin-bottom: 24px;
        border-bottom: 4px solid #0047AB; background: #fff; color: #111;
        font-family: Arial, sans-serif;
    }
    .ch-logo { flex: 0 0 auto; margin-right: 24px; }
    .ch-logo img { height: 80px; width: auto; object-fit: contain; }
    .ch-content { flex: 1; display: flex; flex-direction: column; }
    .ch-title { font-size: 26px; font-weight: 900; text-transform: uppercase; margin: 0 0 12px 0; text-align: center; letter-spacing: 0.5px; }
    .ch-details-row { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; line-height: 1.5; text-transform: uppercase; }
    .ch-details-left { text-align: left; }
    .ch-details-right { text-align: right; }
</style>

<div class="ch-container">
    <div class="ch-logo">
        <img src="{{ asset('images/logo.jpg') }}" alt="Logo">
    </div>
    <div class="ch-content">
        <h1 class="ch-title">{{ settings('company_name', 'JASTENE MEDICAL LTD') }}</h1>
        <div class="ch-details-row">
            <div class="ch-details-left text-left whitespace-pre-line">{{ settings('company_address', 'KIKI BUILDING, KISII-NYAMIRA HIGHWAY.') }}</div>
            <div class="ch-details-right text-right">
                <div>TEL: {{ settings('company_phone', '(+254) 737019207') }}</div>
                <div>EMAIL: <span style="text-transform: lowercase;">{{ settings('company_email', 'info@jastenemedical.com') }}</span></div>
            </div>
        </div>
    </div>
</div>
