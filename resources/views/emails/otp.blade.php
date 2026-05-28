<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Email Verification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F2F2F2; font-family: Arial, sans-serif;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #F2F2F2; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #255059; padding: 20px; color: white; font-size: 24px; font-weight: bold;">
                            Email Verification
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td align="center" style="padding: 30px; color: #333333;">
                            <div style="font-size: 18px; margin-bottom: 20px;">
                                Hello <strong>{{ $userName }}</strong>,
                            </div>
                            <p style="margin-bottom: 20px;">Welcome to ChairPal! Please use the following OTP code to verify your email address.</p>
                            
                            <table border="0" cellspacing="0" cellpadding="0" style="background-color: #F2AD94; border-radius: 8px; margin: 20px auto;">
                                <tr>
                                    <td align="center" style="padding: 20px 40px;">
                                        <p style="font-size: 48px; font-weight: bold; color: #255059; letter-spacing: 5px; margin: 0;">{{ $otpCode }}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="color: #666666; font-size: 14px; margin-top: 20px; line-height: 1.5;">
                                This code is valid for <strong>3 minutes</strong>.<br>
                                Please do not share this code with anyone.
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #255059; padding: 15px; color: white; font-size: 12px; line-height: 1.5;">
                            If you did not request this code, please ignore this email.<br>
                            &copy; {{ date('Y') }} ChairPal. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
