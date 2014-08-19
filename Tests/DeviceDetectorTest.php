<?php
/**
 * Device Detector - The Universal Device Detection library for parsing User Agents
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/lgpl.html LGPL v3 or later
 */
namespace DeviceDetector\Tests;

use DeviceDetector\Cache\CacheMemcache;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use \Spyc;

class DeviceDetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCacheSetAndGet()
    {
        $dd = new DeviceDetector();
        $dd->setCache(new CacheMemcache());
        $this->assertInstanceOf('DeviceDetector\\Cache\\CacheMemcache', $dd->getCache());
    }

    /**
     * @dataProvider getFixtures
     */
    public function testParse($fixtureData)
    {
        $ua = $fixtureData['user_agent'];
        DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_NONE);
        $uaInfo = DeviceDetector::getInfoFromUserAgent($ua);
        $this->assertEquals($fixtureData, $uaInfo);
    }

    public function getFixtures()
    {
        $fixtures = array();
        $fixtureFiles = glob(realpath(dirname(__FILE__)) . '/fixtures/*.yml');
        foreach ($fixtureFiles AS $fixturesPath) {
            $typeFixtures = \Spyc::YAMLLoad($fixturesPath);
            $deviceType = str_replace('_', ' ', substr(basename($fixturesPath), 0, -4));
            if ($deviceType != 'bots') {
                $fixtures = array_merge(array_map(function($elem) {return array($elem);}, $typeFixtures), $fixtures);
            }
        }
        return $fixtures;
    }

    /**
     * @dataProvider getVersionTruncationFixtures
     */
    public function versionTruncationTest($useragent, $truncationType, $osVersion, $clientVersion)
    {
        DeviceParserAbstract::setVersionTruncation($truncationType);
        $dd = new DeviceDetector($useragent);
        $this->assertEquals($osVersion, $dd->getOs('version'));
        $this->assertEquals($clientVersion, $dd->getClient('version'));
    }

    public function getVersionTruncationFixtures()
    {
        return array(
            array('Mozilla/5.0 (Linux; Android 4.2.2; ARCHOS 101 PLATINUM Build/JDQ39) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', DeviceParserAbstract::VERSION_TRUNCATION_NONE, '4.2.2', '34.0.1847.114'),
            array('Mozilla/5.0 (Linux; Android 4.2.2; ARCHOS 101 PLATINUM Build/JDQ39) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', DeviceParserAbstract::VERSION_TRUNCATION_BUILD, '4.2.2', '34.0.1847.114'),
            array('Mozilla/5.0 (Linux; Android 4.2.2; ARCHOS 101 PLATINUM Build/JDQ39) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', DeviceParserAbstract::VERSION_TRUNCATION_PATCH, '4.2.2', '34.0.1847'),
            array('Mozilla/5.0 (Linux; Android 4.2.2; ARCHOS 101 PLATINUM Build/JDQ39) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', DeviceParserAbstract::VERSION_TRUNCATION_MINOR, '4.2', '34.0'),
            array('Mozilla/5.0 (Linux; Android 4.2.2; ARCHOS 101 PLATINUM Build/JDQ39) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', DeviceParserAbstract::VERSION_TRUNCATION_MAJOR, '4', '34'),
        );
    }

    /**
     * @dataProvider getBotFixtures
     */
    public function testParseBots($fixtureData)
    {
        $ua = $fixtureData['user_agent'];
        $dd = new DeviceDetector($ua);
        $dd->parse();
        $this->assertTrue($dd->isBot());
        $botData = $dd->getBot();
        $this->assertEquals($botData['name'], $fixtureData['name']);
        // client and os will always be unknown for bots
        $this->assertEquals($dd->getOs('short_name'), DeviceDetector::UNKNOWN);
        $this->assertEquals($dd->getClient('short_name'), DeviceDetector::UNKNOWN);
    }

    public function getBotFixtures()
    {
        $fixturesPath = realpath(dirname(__FILE__) . '/fixtures/bots.yml');
        $fixtures = \Spyc::YAMLLoad($fixturesPath);
        return array_map(function($elem) {return array($elem);}, $fixtures);
    }

    /**
     * @dataProvider getUserAgents
     */
    public function testTypeMethods($useragent, $isBot, $isMobile, $isDesktop)
    {
        $dd = new DeviceDetector($useragent);
        $dd->parse();
        $this->assertEquals($isBot, $dd->isBot());
        $this->assertEquals($isMobile, $dd->isMobile());
        $this->assertEquals($isDesktop, $dd->isDesktop());
    }

    public function getUserAgents()
    {
        return array(
            array('Googlebot/2.1 (http://www.googlebot.com/bot.html)', true, false, false),
            array('Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.136 Mobile Safari/537.36', false, true, false),
            array('Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)', false, false, true),
            array('Mozilla/3.01 (compatible;)', false, false, false),
        );
    }

}
