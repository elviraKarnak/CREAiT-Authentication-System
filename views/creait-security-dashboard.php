<?php

$user_id = get_current_user_id();

$data = CAS_MFA_Handler::generate_secret($user_id);

$mfa_enabled = get_user_meta($user_id,'creait_mfa_enabled',true);


if(!$mfa_enabled){ ?>

    <h2>Enable Two Factor Authentication</h2>

    <p>Scan this QR with Google or Microsoft Authenticator</p>

    <img src="<?php echo esc_attr($data['qr_image']); ?>" width="200">

    <input type="text" id="mfa-code" placeholder="Enter 6 digit code">

    <button id="enable-mfa-btn">
        Verify & Enable MFA
    </button>

    <div id="mfa-message"></div>

    <script>

        jQuery(function($){

            $('#enable-mfa-btn').on('click', function(){

                let code = $('#mfa-code').val();

                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {

                    action: 'creait_verify_mfa',
                    code: code

                }, function(response){

                    if(response.success){

                        $('#mfa-message').html(
                            '<span style="color:green;">'+response.data+'</span>'
                        );
						location.reload();

                    }else{

                        $('#mfa-message').html(
                            '<span style="color:red;">'+response.data+'</span>'
                        );
						location.reload();

                    }

                });

            });

        });

    </script>

<?php } else{ ?>

    <p style="color:green;font-weight:bold;">
        Two Factor Authentication  is enabled on your account
    </p>

<?php } ?>
