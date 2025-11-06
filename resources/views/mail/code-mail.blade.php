{{-- <x-mail::message>
# Hello {{ $name }} 👋

Use the verification code below to complete your process:

## {{ $code }}

<x-mail::button :url="'https://yourapp.com/verify?code=' . $code">
Verify Account
</x-mail::button>

Thanks for using **{{ config('app.name') }}**!  
We’re glad to have you on board 💙  
</x-mail::message> --}}

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>{{__('messages.mail.title')}}</title>
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
              {{__('messages.mail.title')}}
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:30px; color:#543310; line-height:1.6; font-size:16px;">
              <p>{{__('messages.mail.greeting')}} <strong>{{ $name ?? 'User' }}</strong>,</p>
              <p>{{__('messages.mail.body')}}</p>

              <!-- OTP Box -->
              <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:20px 0;">
                <tr>
                  <td align="center" bgcolor="#f8f4e1" style="padding:5px; border-radius:8px; font-size:28px; font-weight:bold; letter-spacing:5px; color:#543310;">
                    <span>
                      {{ $otp }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <td align="center" bgcolor="#f8f4e1" style="padding:10px;">
                    <a href="{{ $url }}" 
                       style="background-color:#74512d; color:#f8f4e1; padding:12px 25px; border-radius:8px; text-decoration:none; font-size:18px; font-weight:bold; letter-spacing:2px; display:inline-block;">
                       {{__('messages.mail.verify_button')}}
                    </a>
                  </td>
                </tr>
              </table>

              <p>{!! __('messages.mail.expire', ['minutes' => $minutes]) !!}</p>
              <p style="margin-top:10px;">{{ __('messages.mail.remaining_times', ['remain' => $remainTimes, 'max' => $maxTimes]) }}</p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td bgcolor="#543310" style="padding:15px; text-align:center; font-size:12px; color:#f8f4e1;">
              <p style="margin:0;">{{ __('messages.mail.ignore') }}</p>
              <p style="margin:5px 0 0;">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('messages.mail.footer') }}.</p>
            </td>
          </tr>

        </table>
        <!-- End Container -->

      </td>
    </tr>
  </table>

</body>
</html>
