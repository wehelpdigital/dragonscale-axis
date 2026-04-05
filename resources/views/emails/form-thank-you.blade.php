<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f5f7; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #556ee6; padding: 30px 40px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 600;">Salamat sa Iyong Mensahe!</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 40px;">
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Kumusta <strong>{{ $submitterName }}</strong>,
                            </p>

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 0 0 20px;">
                                Maraming salamat sa pagkontak sa amin! Natanggap na namin ang iyong mensahe at babalikan ka namin sa lalong madaling panahon.
                            </p>

                            <!-- Submission Summary -->
                            <div style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                <h3 style="color: #495057; font-size: 14px; margin: 0 0 15px; text-transform: uppercase; letter-spacing: 0.5px;">Buod ng Iyong Submission</h3>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    @foreach($submissionFields as $field)
                                    <tr>
                                        <td style="padding: 6px 0; color: #74788d; font-size: 13px; width: 140px; vertical-align: top;">{{ $field['label'] }}</td>
                                        <td style="padding: 6px 0; color: #333333; font-size: 13px; font-weight: 500;">{{ $field['value'] }}</td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>

                            <p style="color: #495057; font-size: 15px; line-height: 1.6; margin: 25px 0 0;">
                                Kung may karagdagang katanungan ka, huwag mag-atubiling tumugon sa email na ito.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="color: #74788d; font-size: 13px; margin: 0;">
                                &copy; {{ date('Y') }} Ani-Senso. Lahat ng karapatan ay nakalaan.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
