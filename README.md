# Yii2 QQWry

## 查下IP
```php
$ip2Location = new Ip2Location();
$locationModel = $ip2Location->getLocation('8.8.8.8');
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