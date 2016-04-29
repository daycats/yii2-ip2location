# Yii2 QQWry

## 根据IP查询
```php
use \wsl\ip2location\Ip2Location;

$ipLocation = new Ip2Location();
$locationModel = $ipLocation->getLocation('8.8.8.8');
print_r($locationModel->toArray());
// Array
// (
//     [ip] => 8.8.8.8
//     [begin_ip] => 8.8.8.8
//     [end_ip] => 8.8.8.8
//     [country] => 美国
//     [area] => 加利福尼亚州圣克拉拉县山景市谷歌公司DNS服务器
// )
```

## 升级数据库

```php
$qqwry = new QQWry();
$qqwry->upgrade();
```