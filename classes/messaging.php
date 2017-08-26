<?php
namespace cwl;

class messaging {
  static function eMail($to,$from,$subject,$content,$attachments = array()){

		//mail($to,$subject,print_r($content,TRUE));

		require_once('_cwl/lib/PHPMailer/class.phpmailer.php');
		$mail = new \PHPMailer();

		if(is_array($from)){
			$mail->From	= $from[0];
			$mail->FromName = $from[1];
		} else {
			if(strlen($from) > 0){
				$mail->From = $from;
			} else {
				$mail->From = 'cms@' . self::domain();
			}
		}

		if(is_array($to)){
			$mail->AddAddress($to[0],$to[1]);	
		} else {
			if(strlen($to) > 0){
				$mail->AddAddress($to);
			} else {
				$mail->AddAddress('cms@' . self::domain());
			}
		}
		// $mail->AddReplyTo("info@example.com", "Information");

		// $mail->WordWrap = 50;                                 // set word wrap to 50 characters
		
		$mail->IsHTML(true);                                  // set email format to HTML

		if(strlen($subject) > 0){
			$mail->Subject = $subject;	
		} else {
			$subject = self::qp();
			$mail->Subject = $subject;
		}
		
		if(is_array($content)){
			$body = '';
			$body = "<h1>$subject</h1>";
			$body = '<table><tr><th>Field</th><th>Value</th></tr>';
			foreach($content as $key => $data){
				$body = "<tr><td>$key</td><td>$data</td></tr>";
			}
			$body = '</table>';
			$mail->Body = $body;
		} else {
			$mail->Body = $content;
		}
		// $mail->AltBody = "This is the body in plain text for non-HTML mail clients";

		if(is_array($attachments)){
			foreach($attachments as $key => $attachment){
				if(is_numeric($key)){
					$mail->AddAttachment($attachment);	
				} else {
					// $mail->AddAttachment("/tmp/image.jpg", "new.jpg");    // optional name
					$mail->AddAttachment($attachment,$key);	
				}
			}
		}

		// FINALLY! Send the mail!
		$return = $mail->Send();

		if(!$return)
		{
		   echo "Message could not be sent. <p>";
		   echo "Mailer Error: " . $mail->ErrorInfo;
		   exit;
		}

	}
}