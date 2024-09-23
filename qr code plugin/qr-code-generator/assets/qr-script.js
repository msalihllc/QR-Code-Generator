jQuery(document).ready(function($) {
    $('#generate-qr-code').click(function() {
        var qrData = $('#qr-data').val();
        var qrNonce = $('#qr_code_nonce').val();

        if (qrData.trim() === '') {
            alert('Lütfen geçerli bir veri girin!');
            return;
        }

        $.ajax({
            url: qr_code_generator_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_qr_code',
                data: qrData,
                qr_code_nonce: qrNonce // Nonce gönderiliyor
            },
            success: function(response) {
                $('#qr-code-result').html('<img src="' + response + '" alt="QR Code">');
            },
            error: function() {
                alert('QR kodu oluşturulurken bir hata oluştu.');
            }
        });
    });
});
