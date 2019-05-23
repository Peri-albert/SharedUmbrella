<?php
echo $umbrellaID."\n";
echo "已经将umbrellaID传到了其他文件可以在这里书写处理逻辑\n";
header('loction: https://www.sharedumbrella.top/index.php/api/borrow_step2_mqtt_from_terminal?umid='.$umbrellaID);
// $info = json_decode($umbrellaID);
// switch ($info['type']) {
//     case 'umbrella':
//         header('loction: https://www.sharedumbrella.top/index.php/api/borrow_step2_mqtt_from_terminal?umid='.$info['umid'].'&tid='.$info['tid']);
//         break;
    
//     case 'return':
//         header('loction: https://www.sharedumbrella.top/index.php/api/borrow_step2_mqtt_from_terminal?umid='.$info['umid'].'&tid='.$info['tid']);
//         break;
    
//     default:
//         # code...
//         break;
// }
?>
