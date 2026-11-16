<?php
/**
 * Party Box · recepción de consultas con archivos adjuntos.
 * CONFIGURACIÓN: reemplazá la dirección de $destinationEmail por el correo de recepción.
 * Requiere PHP con función mail() configurada en el hosting o integración SMTP.
 * Recomendación de producción: SMTP transaccional autenticado.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$destinationEmail = 'CONFIGURAR_CORREO@ejemplo.com';
$senderEmail = 'formularios@TU-DOMINIO.com';
function fail(int $status, string $text): never {http_response_code($status);echo json_encode(['ok'=>false,'message'=>$text],JSON_UNESCAPED_UNICODE);exit;}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405,'Método no permitido.');
if ($destinationEmail === 'CONFIGURAR_CORREO@ejemplo.com' || $senderEmail === 'formularios@TU-DOMINIO.com') fail(503,'El formulario todavía está en modo de prueba: falta configurar el correo de destino y remitente en enviar.php.');
if (!empty($_POST['sitio_web'])) {echo json_encode(['ok'=>true]);exit;}
function field(string $key, int $length=200): string {return mb_substr(trim((string)($_POST[$key]??'')),0,$length);}
$nombre=field('nombre',120);$medio=field('medio');$email=field('email',160);$telefono=field('telefono',40);$invitados=filter_var($_POST['invitados']??null,FILTER_VALIDATE_INT);$fecha=field('fecha',30);$evento=field('evento');$diseno=field('diseno');$comentarios=field('comentarios',2000);
if (!$nombre || mb_strlen($nombre)<2 || !in_array($medio,['WhatsApp','Email'],true) || !$invitados || $invitados<1 || $invitados>5000 || !in_array($evento,['Cumpleaños','Recibida','Despedida','Divorcio','Nacimiento','Bautismo','Baby shower','Otro'],true) || !in_array($diseno,['Adjunto mis diseños','Solicito diseño'],true) || (string)($_POST['acepto']??'')!=='1') fail(422,'Revisá los campos obligatorios.');
if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) fail(422,'El correo electrónico no es válido.');
if ($medio==='Email' && !$email) fail(422,'Ingresá tu correo electrónico.');
if ($medio==='WhatsApp' && !preg_match('/[0-9]{6,}/',preg_replace('/\D/','',$telefono))) fail(422,'Ingresá tu WhatsApp con código de área.');
if ($fecha!==''){ $d=DateTimeImmutable::createFromFormat('!Y-m-d',$fecha);if(!$d || $d->format('Y-m-d')!==$fecha || $d < (new DateTimeImmutable('today'))->modify('+5 days')) fail(422,'La fecha del evento debe ser al menos 5 días posterior a hoy.');}
$allowedExtensions=['pdf','png','jpg','jpeg','svg','ai','eps','zip'];$attachments=[];$totalBytes=0;
if ($diseno==='Adjunto mis diseños'){
 foreach(['archivo_tapa'=>'Tapa','archivo_heladerita'=>'Heladerita'] as $key=>$label){
  $f=$_FILES[$key]??null;
  if(!$f||$f['error']===UPLOAD_ERR_NO_FILE)continue;
  if($f['error']!==UPLOAD_ERR_OK)fail(422,"Hubo un problema al cargar el archivo de $label.");
  if($f['size']>10*1024*1024 || $f['size']<1)fail(422,"El archivo de $label debe pesar menos de 10 MB.");
  $ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION));
  if(!in_array($ext,$allowedExtensions,true) || !is_uploaded_file($f['tmp_name']))fail(422,"El formato del archivo de $label no está permitido.");
  $totalBytes+=(int)$f['size'];
  if($totalBytes>20*1024*1024)fail(422,'Los archivos adjuntos no pueden superar 20 MB en total.');
  $attachments[]=['tmp'=>$f['tmp_name'],'name'=>($key==='archivo_tapa'?'tapa':'heladerita').'.'.$ext,'type'=>'application/octet-stream'];
 }
}
$body="NUEVA CONSULTA · PARTY BOX\r\n\r\n";
foreach(['Nombre'=>$nombre,'Medio preferido'=>$medio,'WhatsApp'=>$telefono,'Correo'=>$email,'Invitados'=>$invitados,'Fecha del evento'=>$fecha?:'No especificada','Tipo de evento'=>$evento,'Diseño'=>$diseno,'Comentarios'=>$comentarios?:'Sin comentarios'] as $k=>$v){$body.="$k: $v\r\n";}
$boundary='partybox_'.bin2hex(random_bytes(12));
$headers=['MIME-Version: 1.0','From: Party Box <'.$senderEmail.'>','Content-Type: multipart/mixed; boundary="'.$boundary.'"'];
if($email)$headers[]='Reply-To: '.$email;
$message="--$boundary\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($body));
foreach($attachments as $attachment){$content=file_get_contents($attachment['tmp']);if($content===false)fail(500,'No se pudo procesar un archivo adjunto.');$message.="--$boundary\r\nContent-Type: ".$attachment['type']."; name=\"".$attachment['name']."\"\r\nContent-Disposition: attachment; filename=\"".$attachment['name']."\"\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($content));}
$message.="--$boundary--\r\n";
$success=@mail($destinationEmail,'Nueva consulta Party Box - '.$evento,$message,implode("\r\n",$headers));
if(!$success)fail(500,'No pudimos enviar la consulta. Contactá al comercio directamente o intentá más tarde.');
echo json_encode(['ok'=>true,'message'=>'Consulta enviada.'],JSON_UNESCAPED_UNICODE);
