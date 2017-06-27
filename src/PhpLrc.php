<?php
/**
* @file phplrc.php
* @description lrc歌词文件解析库
* @author changwei <867597730@qq.com>
* @website https://github.com/cw1997/php-lrc
* @date 2017-06-27 00:30:49
*/

namespace Changwei;

class PhpLrc
{

	/**
	 * @desc: lrc文件内容
	 * @var _content
	 */
	private $_content;

	/**
	 * @desc: 数组键名为原始时间格式
	 * @example_key: 00:00.00
	 */
	const NORMAL = 0;
	/**
	 * @desc: 数组键名为毫秒
	 * @example_key: 123450
	 */
	const MSECOND = 1;

	/**
	 * 构造方法
	 * @param string $filepath lrc歌词文件完整路径（可为url）
	 */
	function __construct($filepath)
	{
		if (empty($filepath)) {
			throw new \Exception("Error Reading Lrc File", 1);
		}
		$this->_content = $this->_getContentFromLrc($filepath);
		if (empty($this->_content)) {
			throw new \Exception("Lrc File Is Empty", 1);
		}
	}

	/**
	 * 获取获取歌词与时刻关联数组
	 * @param  int $keyType 枚举类型，常量为PhpLrc::NORMAL或PhpLrc::MSECOND
	 * @return array          时间歌词关联数组
	 */
	public function getArrayByLrc($keyType)
	{
		$ret = array();
		switch ($keyType) {
			case self::NORMAL:
				$raw_content = $this->decompress();
				foreach ($raw_content as $key => $value) {
					$ret[$this->_formatRawTimeToMsec($key)] = $value;
				}
				// $ret = $this->_formatRawTimeToMsec($this->decompress());
				break;

			case self::MSECOND:
				$ret = $this->decompress();
				break;

			default:
				break;
		}
		return $ret;
	}

	/**
	 * 压缩歌词
	 * @return array 时间歌词关联数组
	 */
	public function compress()
	{
		$ret = array();
		$result_all = $this->decompress();
		// var_dump($result_all);
		$temp = array_flip($result_all);
		// var_dump($temp);
		foreach ($temp as $key => $value) {
			$sec = array();
			while ($search_result = array_search($key, $result_all)) {
				$sec[] = "[{$search_result}]";
				unset($result_all[$search_result]);
			}
			$ret[$key] = implode('', $sec);
			// var_dump($sec);
		}
		$ret = array_flip($ret);
		return $ret;
	}

	/**
	 * 解压缩歌词
	 * @return array 时间歌词关联数组
	 */
	public function decompress()
	{
		$ret = array();
		$result_all = $this->_formatContent();
		$line = $result_all[0];
		$raw_time = $result_all[1];
		$raw_lyric = $result_all[3];
		$pattern_time = "/\[[0-2][0-4]:[0-5][0-9]\.[0-9][0-9]\]/i";
		foreach ($raw_time as $key => $value) {
			preg_match_all($pattern_time, $value, $result_time);
			// var_dump($result_time);
			foreach ($result_time[0] as $time_index => $time_value) {
				$time_value = $this->_formatRawTimeToMsec($time_value);
				$ret[$time_value] = $raw_lyric[$key];
			}
		}
		ksort($ret);
		$ret = $this->_formatKeyToRawTime($ret);
		return $ret;
	}

	/**
	 * 保存歌词数组到文件
	 * @param  array  $lrcArray 通过compress或者deconmpress方法生成的数组
	 * @param  string $filepath lrc文件保存路径
	 * @return int           lrc文件写入字节大小
	 */
	public static function storeToFile(array $lrcArray, $filepath)
	{
		$isCompress = self::_checkLrcArray($lrcArray);
		// var_dump($isCompress);
		$arrContent = array();
		if ($isCompress) {
			// 使用use关键字将$arrContent的引用传入闭包内部
			array_walk($lrcArray, function ($value, $key) use (&$arrContent)
			{
				$arrContent[] = "{$key}{$value}";
			});
		} else {
			// 非压缩lrc文件，键名两端加方括号[]
			array_walk($lrcArray, function ($value, $key) use (&$arrContent)
			{
				$arrContent[] = "[{$key}]{$value}";
			});
		}
		$strContent = implode("\n", $arrContent);
		// var_dump($strContent);
		return file_put_contents($filepath, $strContent);
	}

	/**
	 * 通过文件路径获取歌词文件实际内容
	 * @param  string $filepath 歌词文件完整路径
	 * @return string           歌词文件实际内容]
	 */
	private function _getContentFromLrc($filepath)
	{
		return file_get_contents($filepath);
	}

	/**
	 * 格式化lrc歌词文件内容
	 * @return array 时间歌词关联数组
	 */
	private function _formatContent()
	{
		$pattern_all = "/((\[[0-2][0-4]:[0-5][0-9]\.[0-9][0-9]\])+)(.*)/i";
		preg_match_all($pattern_all, $this->_content, $result_all);
		// var_dump($result_all);
		return $result_all;
	}

	/**
	 * 检测是否为压缩时间
	 * @param  array  $lrcArray 歌词数组
	 * @return bool           true表示为压缩时间
	 */
	private static function _checkLrcArray(array $lrcArray)
	{
		$min_key_len = strlen('00:00.00');
		foreach ($lrcArray as $key => $value) {
			if (strlen($key) > $min_key_len) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 格式化原始时间格式为毫秒
	 * @param  string $raw_time 原始时间
	 * @return int           毫秒数
	 */
	private function _formatRawTimeToMsec($raw_time)
	{
		$pattern = "/([0-2][0-4]):([0-5][0-9])\.([0-9][0-9])/i";
		$result = array();
		preg_match($pattern, $raw_time, $result);
		// var_dump($result);
		if (count($result) !== 4) {
			return '';
		}
		$min = $result[1];
		$sec = $result[2];
		$msec = $result[3];
		$ret = $min * 60 * 1000 + $sec * 1000 + $msec * 10;
		// var_dump($ret);
		return $ret;
	}

	/**
	 * 格式化毫秒为原始时间格式
	 * @param  int $msec_time 毫秒
	 * @return string           原始时间
	 */
	private function _formatMsecTimeToRaw($msec_time)
	{
		$min = floor($msec_time / 1000 / 60);
		$sec = floor(($msec_time - $min * 60 * 1000) / 1000);
		$msec = floor(($msec_time - $min * 60 * 1000 - $sec * 1000) % 1000);
		$min = str_pad($min, 2, '0', STR_PAD_LEFT);
		$sec = str_pad($sec, 2, '0', STR_PAD_LEFT);
		// 这里考虑到毫秒数可能尾部带0的情况
		$msec = str_pad(substr($msec, 0, 2), 2, '0', STR_PAD_LEFT);
		$ret = "{$min}:{$sec}.{$msec}";
		// var_dump($ret);
		return $ret;
	}

	/**
	 * 格式化歌词时间关联数组的键为原始时间格式
	 * @param  array  $arr 时间歌词关联数组
	 * @return array      时间歌词关联数组
	 */
	private function _formatKeyToRawTime(array $arr)
	{
		$ret = array();
		foreach ($arr as $key => $value) {
			$new_key = $this->_formatMsecTimeToRaw($key);
			$ret[$new_key] = $value;
		}
		return $ret;
	}

	/**
	 * 格式化歌词时间关联数组的键为毫秒
	 * @param  array  $arr 时间歌词关联数组
	 * @return array      时间歌词关联数组
	 */
	private function _formatKeyToMsecTime(array $arr)
	{
		$ret = array();
		foreach ($arr as $key => $value) {
			$new_key = $this->_formatRawTimeToMsec($key);
			$ret[$new_key] = $value;
		}
		return $ret;
	}
}
