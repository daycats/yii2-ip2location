<?php
/**
 * Created by PhpStorm.
 * User: shanli
 * Date: 2016/4/22
 * Time: 17:08
 */

namespace wsl\qqwry;


use wsl\qqwry\exceptions\LocationException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * 纯真数据库操作类
 *
 * @package wsl\qqwry
 */
class QQWry
{
    /**
     * @var string 数据库url
     */
    public $copyWriteUrl = 'http://update.cz88.net/ip/copywrite.rar';
    /**
     * @var string 数据库url
     */
    public $qqWryUrl = 'http://update.cz88.net/ip/qqwry.rar';

    /**
     * 数据库升级
     *
     * @param string $savePath 数据库保存路径
     * @return bool
     * @throws LocationException
     * @throws \yii\base\Exception
     */
    public function upgrade($savePath = '@wsl/qqwry/data/qqwry.dat')
    {
        $savePath = Yii::getAlias($savePath);
        if (!FileHelper::createDirectory(dirname($savePath), 0777)) {
            throw new LocationException($savePath . ' is not write');
        }

        $copyWriteContent = file_get_contents($this->copyWriteUrl);
        $qqWryFileContent = file_get_contents($this->qqWryUrl);

        $key = ArrayHelper::getValue(unpack('V6', $copyWriteContent), 6);
        if (!$key) {
            return false;
        }

        for ($i = 0; $i < 0x200; $i++) {
            $key *= 0x805;
            $key++;
            $key = $key & 0xFF;
            $qqWryFileContent[$i] = chr(ord($qqWryFileContent[$i]) ^ $key);
        }

        $qqWryFileContent = gzuncompress($qqWryFileContent);
        $fp = fopen($savePath, 'wb');
        if ($fp) {
            fwrite($fp, $qqWryFileContent);
            fclose($fp);
        }

        return true;
    }
}