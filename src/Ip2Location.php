<?php
/**
 * Created by PhpStorm.
 * User: shanli
 * Date: 2016/4/22
 * Time: 16:29
 */

namespace wsl\qqwry;

use wsl\qqwry\exceptions\LocationException;
use wsl\qqwry\models\Location;
use Yii;

/**
 * ip转地址
 *
 * @package wsl\qqwry
 */
class Ip2Location
{
    /**
     * @var string 编码
     */
    public $encoding = 'UTF-8';
    /**
     * @var bool|string 数据库路径
     */
    protected $dbFilePath;
    /**
     * @var resource 数据库文件句柄
     */
    protected $dbFileFp;
    /**
     * @var int 索引开始偏移
     */
    protected $startOffset;
    /**
     * @var int 索引接收偏移
     */
    protected $endOffset;
    /**
     * @var float 总长度
     */
    protected $total;

    /**
     * Ip2Location constructor.
     *
     * @param string $dbFilePath 数据库文件路径
     * @throws LocationException
     */
    public function __construct($dbFilePath = '@wsl/qqwry/data/qqwry.dat')
    {
        $this->file = Yii::getAlias($dbFilePath);
        if (!file_exists($this->file) or !is_readable($this->file)) {
            throw new LocationException($this->file . ' does not exist, or is not readable');
        }
        $this->dbFileFp = fopen($this->file, 'rb');
        $this->startOffset = join('', unpack('L', $this->readOffset(4, 0)));
        var_dump($this->startOffset);
        $this->endOffset = join('', unpack('L', $this->readOffset(4)));
        $this->total = ($this->endOffset - $this->startOffset) / 7 + 1;
    }

    public function __destruct()
    {
        if ($this->dbFileFp) {
            fclose($this->dbFileFp);
        }
    }

    /**
     * 数值型IP转文本型IP
     *
     * @param number $nip
     * @return string
     */
    public function ntoa($nip)
    {
        $ip = [];
        for ($i = 3; $i > 0; $i--) {
            $ip_seg = intval($nip / pow(256, $i));
            $ip[] = $ip_seg;
            $nip -= $ip_seg * pow(256, $i);
        }
        $ip[] = $nip;

        return join('.', $ip);
    }

    /**
     * IP查询
     *
     * @param string $ip 要查下的IP
     * @throws LocationException
     * @return Location
     */
    public function query($ip)
    {
        $ipSplit = explode('.', $ip);
        if (count($ipSplit) !== 4) {
            throw new LocationException($ip . ' is not a valid ip address');
        }
        foreach ($ipSplit as $v) {
            if ($v > 255) {
                throw new LocationException($ip . ' is not a valid ip address');
            }
        }
        $ipNum = $ipSplit[0] * (256 * 256 * 256) + $ipSplit[1] * (256 * 256) + $ipSplit[2] * 256 + $ipSplit[3];
        $ipFind = $this->find($ipNum, 0, $this->total);
        $ipOffset = $this->startOffset + $ipFind * 7 + 4;
        $ipRecordOffset = $this->readOffset(3, $ipOffset);
        $ipRecordOffset = join('', unpack('L', $ipRecordOffset . chr(0)));

        return $this->readRecord($ipRecordOffset);
    }

    /**
     * 读取记录
     *
     * @param int $offset 偏移量
     * @return Location
     */
    protected function readRecord($offset)
    {
        $offset = $offset + 4;
        $flag = ord($this->readOffset(1, $offset));

        $locationModel = new Location();

        switch ($flag) {
            case 1:
                $locationOffset = $this->readOffset(3, $offset + 1);
                $locationOffset = join('', unpack('L', $locationOffset . chr(0)));

                $subFlag = ord($this->readOffset(1, $locationOffset));

                if ($subFlag == 2) {
                    // 国家
                    $countryOffset = $this->readOffset(3, $locationOffset + 1);
                    $countryOffset = join('', unpack('L', $countryOffset . chr(0)));
                    $locationModel->country = $this->readLocation($countryOffset);
                    $locationModel->area = $this->readLocation($locationOffset + 4); // 地区
                } else {
                    $locationModel->country = $this->readLocation($locationOffset);
                    $locationModel->area = $this->readLocation($locationOffset + strlen($locationModel->country) + 1);
                }
                break;
            case 2:
                // 地区
                // offset + 1(flag) + 3(country offset)
                $locationModel->area = $this->readLocation($offset + 4);

                // offset + 1(flag)
                $countryOffset = $this->readOffset(3, $offset + 1);
                $countryOffset = join('', unpack('L', $countryOffset . chr(0)));
                $locationModel->country = $this->readLocation($countryOffset);
                break;
            default:
                $locationModel->country = $this->readLocation($offset);
                $locationModel->area = $this->readLocation($offset + strlen($locationModel->country) + 1);
        }

        // 转换编码并去除无信息时显示的CZ88.NET
        $location = array_map(function ($item) {
            if (function_exists('mb_convert_encoding')) {
                $item = mb_convert_encoding($item, $this->encoding, 'GBK');
            } else {
                $item = iconv('GBK', $this->encoding . '//IGNORE', $item);
            }
            return preg_replace('/\s*cz88\.net\s*/i', '', $item);
        }, $locationModel->toArray());
        $locationModel->setAttributes($location, false);

        return $locationModel;
    }

    /**
     * 读取地区
     *
     * @param int $offset 偏移
     * @return string
     */
    protected function readLocation($offset)
    {
        if ($offset == 0) {
            return '';
        }

        $flag = ord($this->readOffset(1, $offset));

        // 出错
        if ($flag == 0) {
            return '';
        }

        // 仍然为重定向
        if ($flag == 2) {
            $offset = $this->readOffset(3, $offset + 1);
            $offset = join('', unpack('L', $offset . chr(0)));
            return $this->readLocation($offset);
        }

        $location = '';
        $chr = $this->readOffset(1, $offset);
        while (ord($chr) != 0) {
            $location .= $chr;
            $offset++;
            $chr = $this->readOffset(1, $offset);
        }

        return $location;
    }

    /**
     * 查找 ip 所在的索引
     *
     * @param int $ipLong
     * @param int $l
     * @param int $r
     * @return mixed
     */
    protected function find($ipLong, $l, $r)
    {
        if ($l + 1 >= $r) {
            return $l;
        }
        $m = intval(($l + $r) / 2);

        $find = $this->readOffset(4, $this->startOffset + $m * 7);
        $mIp = join('', unpack('L', $find));

        if ($ipLong < $mIp) {
            return $this->find($ipLong, $l, $m);
        } else {
            return $this->find($ipLong, $m, $r);
        }
    }

    /**
     * 读取
     *
     * @param int $length 读取长度
     * @param null|int $offset 偏移
     * @return int
     */
    protected function readOffset($length, $offset = null)
    {
        if (!is_null($offset)) {
            fseek($this->dbFileFp, $offset);
        }
        return fread($this->dbFileFp, $length);
    }
}