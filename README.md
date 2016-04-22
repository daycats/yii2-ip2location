# Yii2 QQWry

## 查下IP
```php
$qqwry = new IpLocation();
print_r($qqwry->query('120.42.46.10'));
```

## 升级数据库

```php
$qqwry = new QQWry();
var_dump($qqwry->upgrade());
```