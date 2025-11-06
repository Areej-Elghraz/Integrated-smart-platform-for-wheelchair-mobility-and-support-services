

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.title')); ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f4; font-family: Arial, sans-serif;">

  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f4f4" style="padding:20px 0;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="500" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="border-radius:8px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
          
          <!-- Header -->
          <tr>
            <td align="center" bgcolor="#543310" style="padding:20px; color:#f8f4e1; font-size:22px; font-weight:bold;">
              <?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.title')); ?>

            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:30px; color:#543310; line-height:1.6; font-size:16px;">
              <p><?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.greeting')); ?> <strong><?php echo new \Illuminate\Support\EncodedHtmlString($name ?? 'User'); ?></strong>,</p>
              <p><?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.body')); ?></p>

              <!-- OTP Box -->
              <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:20px 0;">
                <tr>
                  <td align="center" bgcolor="#f8f4e1" style="padding:5px; border-radius:8px; font-size:28px; font-weight:bold; letter-spacing:5px; color:#543310;">
                    <span>
                      <?php echo new \Illuminate\Support\EncodedHtmlString($otp); ?>

                    </span>
                  </td>
                </tr>
                <tr>
                  <td align="center" bgcolor="#f8f4e1" style="padding:10px;">
                    <a href="<?php echo new \Illuminate\Support\EncodedHtmlString($url); ?>" 
                       style="background-color:#74512d; color:#f8f4e1; padding:12px 25px; border-radius:8px; text-decoration:none; font-size:18px; font-weight:bold; letter-spacing:2px; display:inline-block;">
                       <?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.verify_button')); ?>

                    </a>
                  </td>
                </tr>
              </table>

              <p><?php echo __('messages.mail.expire', ['minutes' => $minutes]); ?></p>
              <p style="margin-top:10px;"><?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.remaining_times', ['remain' => $remainTimes, 'max' => $maxTimes])); ?></p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td bgcolor="#543310" style="padding:15px; text-align:center; font-size:12px; color:#f8f4e1;">
              <p style="margin:0;"><?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.ignore')); ?></p>
              <p style="margin:5px 0 0;">&copy; <?php echo new \Illuminate\Support\EncodedHtmlString(date('Y')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString(config('app.name')); ?>. <?php echo new \Illuminate\Support\EncodedHtmlString(__('messages.mail.footer')); ?>.</p>
            </td>
          </tr>

        </table>
        <!-- End Container -->

      </td>
    </tr>
  </table>

</body>
</html>
<?php /**PATH D:\apiCourse\API_Projects\Chairpal\resources\views/mail/code-mail.blade.php ENDPATH**/ ?>