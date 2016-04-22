# Yii2 QQWry

## 查下IP
```php
$ip2Location = new Ip2Location();
$locationModel = $ip2Location->query('8.8.8.8');
print_r($locationModel->toArray());
// Array
// (
//      [country] => 美国
//      [area] => 加利福尼亚州圣克拉拉县山景市谷歌公司DNS服务器
// )
```

## 升级数据库

```php
$qqwry = new QQWry();
$qqwry->upgrade();
```