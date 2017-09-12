<?php
$_GET['_debug'] = TRUE;
include('cwl.php');

$mail     = imap_open('{imap.1and1.co.uk:993/imap/ssl}INBOX', 'blog@cwlynch.com', 'Bl0gByeMail!');
$headers  = imap_headers($mail);

for ($n=1; $n<=count($headers); $n++) {
 
  $header = imap_fetch_overview($mail,$n);
  $body = imap_fetchstructure($mail, $n);
  print_r($body);
  
  $item = new cwl\noSQLstdClass();
  $item->type = 'post';
  $item->name = $header[0]->subject;
  $item->html = '';
  $item->image = array();
  $item->status = 1;
  
  if (count($body->parts) == 0) {
    // SINGLE part email
    $item->html = imap_qprint(imap_body($mail,$n));  
  } elseif (!isset($body->parts[0]->parts)) {
    // MULTI part email without attachments
    $item->html = quoted_printable_decode(imap_fetchbody($mail,$n,'1'));
  } else {
    // MULTI part email with attachments
    $item->HTML = quoted_printable_decode(imap_fetchbody($mail,$n,'1.1'));
    foreach($body->parts as $key => $part){
      if($part->encoding == 3){
        // This could be a
        if(@$part->dparameters[0]->attribute == 'filename'){
          file_put_contents($part->dparameters[0]->value,base64_decode(imap_fetchbody($mail, $n, $key+1)));
          // Add an item to the files array ready to be saved.
          $item->image[] = $part->dparameters[0]->value;
        }
      }
    }
  }
  
  // Media field
  if(stripos($item->html,"http://") === 0 || stripos($item->html,"https://") === 0){
    $HTML = explode("\n",$item->html);
    $item->media = array_shift($HTML);
    if(strstr($item->media,'youtube')){
      $item->format = 'video';
    } else {
      $item->format = 'link';
    }
    $item->html = implode("\n",$HTML);
  }
  
  cwl\nosql::save($item);
  
  print_r($item);
  
  imap_delete($mail,$n);
  
}
imap_expunge($mail);
imap_close($mail);


?>