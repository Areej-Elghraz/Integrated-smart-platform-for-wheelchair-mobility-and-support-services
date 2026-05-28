<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Request Accepted</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F2F2F2; font-family: Arial, sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #F2F2F2; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #255059; padding: 20px; color: white; font-size: 24px; font-weight: bold;">
                            Connection Request Accepted
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td align="center" style="padding: 30px; color: #333333;">
                            <div style="font-size: 18px; margin-bottom: 20px;">
                                Hello <strong><?php echo e($user->name); ?></strong>,
                            </div>
                            <p style="margin-bottom: 20px; font-size: 16px;">
                                Great news! <strong><?php echo e($targetUser->name); ?></strong> has accepted your request.
                            </p>
                            <p style="margin-bottom: 20px; font-size: 16px; color: #666666;">
                                You can now access their dashboard data and connect directly from your app.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #255059; padding: 15px; color: white; font-size: 12px; line-height: 1.5;">
                            &copy; <?php echo e(date('Y')); ?> ChairPal. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH D:\apiCourse\API_Projects\Chairpal\resources\views/emails/request_accepted.blade.php ENDPATH**/ ?>