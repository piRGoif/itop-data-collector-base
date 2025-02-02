<?php

namespace UnitTestFiles\Test;

use PHPUnit\Framework\TestCase;
use Utils;

@define('APPROOT', dirname(__FILE__, 2).'/');

require_once(APPROOT.'core/utils.class.inc.php');
require_once(APPROOT.'core/parameters.class.inc.php');

class UtilsTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
		Utils::MockDoPostRequestService(null);

		$reflection = new \ReflectionProperty(Utils::class, 'oConfig');
		$reflection->setAccessible(true);
		$reflection->setValue(null, null);
	}

	public function ComputeCurlOptionsProvider(){
		return [
			'nominal usecase: constant key/ constant int value' => [
				'aRawCurlOptions' =>  [
					CURLOPT_SSLVERSION => CURL_SSLVERSION_SSLv3,
				],
				'aExpectedReturnedOptions' => [
					32 => 3,
					78 => 600,
					13 => 600,
				]
			],
			'nominal usecase: constant key/ constant provided as a string' => [
				'aRawCurlOptions' =>  [
					CURLOPT_SSLVERSION => 'CURL_SSLVERSION_SSLv3',
				],
				'aExpectedReturnedOptions' => [
					32 => 3,
					78 => 600,
					13 => 600,
				]
			],
			'constant provided as a string key/ string value' => [
				'aRawCurlOptions' =>  [
					'CURLOPT_COOKIE' => 'itop',
				],
				'aExpectedReturnedOptions' => [
					78 => 600,
					13 => 600,
					10022 => 'itop',
				]
			],
			'constant key/ constant boolean value' => [
				'aRawCurlOptions' =>  [
					CURLOPT_COOKIESESSION => true,
				],
				'aExpectedReturnedOptions' => [
					78 => 600,
					13 => 600,
					96 => true,
				]
			],
		];
	}

	/**
	 * @dataProvider ComputeCurlOptionsProvider
	 */
	public function testComputeCurlOptions($aRawCurlOptions, $aExpectedReturnedOptions){
		$aCurlOptions = Utils::ComputeCurlOptions($aRawCurlOptions, 600);

		$this->assertEquals($aExpectedReturnedOptions, $aCurlOptions);
	}


	public function ComputeCurlOptionsTimeoutProvider(){
		return [
			'with timeout' => [
				'aExpectedReturnedOptions' => [
					78 => 600,
					13 => 600,
				],
				'iTimeout' => 600
			],
			'without timeout' => [
				'aExpectedReturnedOptions' => [],
				'iTimeout' => -1
			],
		];
	}

	/**
	 * @dataProvider ComputeCurlOptionsTimeoutProvider
	 */
	public function testComputeCurlOptionsTimeoutProvider($aExpectedReturnedOptions, $iTimeout){
		$aRawCurlOptions = [];

		$aCurlOptions = Utils::ComputeCurlOptions($aRawCurlOptions, $iTimeout);

		$this->assertEquals($aExpectedReturnedOptions, $aCurlOptions);
	}

	public function GetCredentialsProvider(){
		return [
			'login/password (nominal)' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2'
				],
				'aExpectedCredentials' => ['auth_user'=> 'admin1', 'auth_pwd'=>'admin2']
			],
			'new token' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2',
					'itop_token' => 'admin4',
				],
				'aExpectedCredentials' => ['auth_token'=> 'admin4']
			],
			'new token over legacy one' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2',
					'itop_rest_token' => 'admin3',
					'itop_token' => 'admin4',
				],
				'aExpectedCredentials' => ['auth_token'=> 'admin4']
			],
		];
	}

	/**
	 * @dataProvider GetCredentialsProvider
	 */
	public function testGetCredentials($aParameters, $aExpectedCredentials){
		$oParametersMock = $this->createMock(\Parameters::class);
		$oParametersMock->expects($this->atLeast(1))
			->method('Get')
			->will($this->returnCallback(
				function($sKey, $aDefaultValue) use ($aParameters) {
					if (array_key_exists($sKey, $aParameters)){
						return $aParameters[$sKey];
					}
					return $aDefaultValue;
				}
			));

		$reflection = new \ReflectionProperty(Utils::class, 'oConfig');
		$reflection->setAccessible(true);
		$reflection->setValue(null, $oParametersMock);

		$this->assertEquals($aExpectedCredentials, Utils::GetCredentials());
	}

	/**
	 * @dataProvider GetLoginModeProvider
	 */
	public function testGetLoginForm($aParameters, $sExpectedLoginMode){
		$oParametersMock = $this->createMock(\Parameters::class);
		$oParametersMock->expects($this->atLeast(1))
			->method('Get')
			->will($this->returnCallback(
				function($sKey, $aDefaultValue) use ($aParameters) {
					if (array_key_exists($sKey, $aParameters)){
						return $aParameters[$sKey];
					}
					return $aDefaultValue;
				}
			));

		$reflection = new \ReflectionProperty(Utils::class, 'oConfig');
		$reflection->setAccessible(true);
		$reflection->setValue(null, $oParametersMock);

		$this->assertEquals($sExpectedLoginMode, Utils::GetLoginMode());
	}

	public function GetLoginModeProvider(){
		return [
			'login/password (nominal)' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2'
				],
				'sExpectedLoginMode' => 'form'
			],
			'authent-token v2' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2',
					'itop_token' => 'admin4',
				],
				'sExpectedLoginMode' => 'token'
			],
			'new token over legacy one' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2',
					'itop_rest_token' => 'admin3',
					'itop_token' => 'admin4',
				],
				'sExpectedLoginMode' => 'token'
			],
			'itop_login_mode over others' => [
				'aParameters' => [
					'itop_login' => 'admin1',
					'itop_password' => 'admin2',
					'itop_rest-token' => 'admin3',
					'itop_token' => 'admin4',
					'itop_login_mode' => 'newloginform',
				],
				'sExpectedLoginMode' => 'newloginform'
			],
		];
	}


}
