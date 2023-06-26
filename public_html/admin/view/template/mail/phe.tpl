<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title><?php echo $subject; ?></title>
  </head>
  <body style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; width: 100% !important; height: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: none; background: #ffffff; margin: 0; padding: 0;">
    <table style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; width: 100% !important; height: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: none; background: #ffffff; margin: 0; padding: 0;">
      <tr style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; margin: 0; padding: 0;">
        <td style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; display: block !important; clear: both !important; max-width: 580px !important; margin: 0 auto; padding: 0;">
          <table style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; width: 100% !important; border-collapse: collapse; margin: 0; padding: 0;">
            <tr style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; margin: 0; padding: 0;">
              <td style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; color: #ffffff; background: <?php echo $background; ?>; margin: 0; padding: 80px 0; text-align: center;">
                <?php if ($logo) { ?>
				<a href="<?php echo $url; ?>"><img src="<?php echo $logo; ?>" alt="<?php echo $store_name; ?>" title="<?php echo $store_name; ?>" /></a>
                <?php } else { ?>
				<a href="<?php echo $url; ?>" style="font-size: 24px; font-family: Helvetica, Arial, sans-serif; line-height: 1.25; max-width: 90%; margin: 0 auto; padding: 0; text-align: center; color: #ffffff;"><?php echo $store_name; ?></a>
				<?php } ?>
			  </td>
            </tr>
            <tr style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; margin: 0; padding: 0;">
              <td style="font-size: 100%; font-family: Helvetica, Arial, sans-serif; line-height: 1.65; background: <?php echo $body; ?>; margin: 0; padding: 30px 35px;">
                <div style="font-size:16px;font-weight:bold;border-bottom:1px solid #aaaaaa;color:<?php echo $heading; ?>"><?php echo $subject; ?></div>
                <?php echo $message; ?>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>