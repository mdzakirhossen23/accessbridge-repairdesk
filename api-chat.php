<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405); echo json_encode(['error'=>'POST required']); exit;
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== preg_replace('/:\\d+$/', '', $host)) {
 http_response_code(403); echo json_encode(['error'=>'Origin not allowed']); exit;
}
ini_set('display_errors', '0');
session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Strict']);
session_start();
$now=time();
$requests=array_values(array_filter($_SESSION['ai_requests']??[],static fn($stamp)=>is_int($stamp)&&$stamp>$now-60));
if(count($requests)>=20){http_response_code(429);echo json_encode(['error'=>'Please wait before sending another question.']);exit;}
$requests[]=$now;
$_SESSION['ai_requests']=$requests;
session_write_close();
$raw=file_get_contents('php://input');
if($raw===false||strlen($raw)>18000){http_response_code(413);echo json_encode(['error'=>'Request is too large.']);exit;}
$input=json_decode($raw,true);
if(!is_array($input)){http_response_code(400);echo json_encode(['error'=>'Invalid JSON.']);exit;}
$languageNames=['en'=>'English','bn'=>'Bangla','ar'=>'Arabic','nl'=>'Dutch','de'=>'German'];
$language=isset($input['language'],$languageNames[$input['language']])?$input['language']:'en';
if(!isset($input['message'])||!is_string($input['message'])){http_response_code(400);echo json_encode(['error'=>'A text question is required.']);exit;}
$message=trim($input['message']);
$action=trim((string)($input['action']??'answer'));
if(!in_array($action,['answer','education','career','digital'],true))$action='answer';
$subject=mb_substr(trim((string)($input['subject']??'General assistance')),0,160);
$goal=mb_substr(trim((string)($input['goal']??'')),0,500);
if($message===''||mb_strlen($message)>4000){http_response_code(400);echo json_encode(['error'=>'A question between 1 and 4000 characters is required.']);exit;}
// Keep credentials outside public_html.
$secretDir=dirname(__DIR__,2);
$secretFile=$secretDir.DIRECTORY_SEPARATOR.'.accessbridge-groq-key.php';
if(!is_file($secretFile))$secretFile=$secretDir.DIRECTORY_SEPARATOR.'.chatsprint-groq-key.php';
if(!is_file($secretFile)){http_response_code(503);echo json_encode(['error'=>'AI service is not configured yet.']);exit;}
$apiKey=require $secretFile;
if(!is_string($apiKey)||!str_starts_with($apiKey,'gsk_')){http_response_code(503);echo json_encode(['error'=>'AI service configuration is invalid.']);exit;}
$system='You are AccessBridge AI, a multilingual digital inclusion assistant. Reply only in '.$languageNames[$language].'. '
 .'Help people learn, prepare for careers, understand online services, and use accessible technology. Be clear, accurate, practical and encouraging. '
 .'Explain step by step, avoid inventing official websites, fees, legal requirements or deadlines. For official services, advise checking the official authority. '
 .'Never ask for passwords, one-time codes, banking data or identity document numbers. Do not pretend you have submitted an application. '
 .'Provide repair guidance in under 100 words. Never invent repair prices or timings. Ask users to obtain a written quote. Never advise opening equipment, handling swollen batteries or bypassing device locks. Topic: '.$subject.'. User goal: '.$goal.'. Section: '.$action.'.';
$messages=[['role'=>'system','content'=>$system]];
// Client history is intentionally ignored.
$messages[]=['role'=>'user','content'=>mb_substr($message,0,4000)];
$payload=json_encode(['model'=>'openai/gpt-oss-20b','messages'=>$messages,'temperature'=>0.35,'max_completion_tokens'=>700,'reasoning_effort'=>'low'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$curl=curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>28,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$apiKey],CURLOPT_POSTFIELDS=>$payload]);
$response=curl_exec($curl);
$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
$curlError=curl_error($curl);
curl_close($curl);
if($response===false||$status<200||$status>=300){http_response_code($status===429?429:502);echo json_encode(['error'=>$status===429?'The free AI limit is temporarily busy. Please try again shortly.':'The AI coach could not respond.']);exit;}
$data=json_decode($response,true);
$answer=trim((string)($data['choices'][0]['message']['content']??''));
if($answer===''){http_response_code(502);echo json_encode(['error'=>'The AI coach returned an empty response.']);exit;}
echo json_encode(['answer'=>$answer,'model'=>'openai/gpt-oss-20b'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
