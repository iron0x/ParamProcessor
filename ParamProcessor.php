<?php

/**
 * Initialization file for the ParamProcessor library.
 * Extension documentation: http://www.mediawiki.org/wiki/Extension:ParamProcessor
 *
 * You will be validated. Resistance is futile.
 *
 * @file
 * @ingroup ParamProcessor
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * This documentation group collects source code files belonging to ParamProcessor.
 *
 * Please do not use this group name for other code.
 *
 * @defgroup ParamProcessor ParamProcessor
 */

if ( defined( 'ParamProcessor_VERSION' ) ) {
	// Do not initialize more then once.
	return;
}

define( 'ParamProcessor_VERSION', '1.0 beta' );
define( 'Validator_VERSION', ParamProcessor_VERSION ); // @deprecated since 1.0

global $wgVersion, $wgExtensionMessagesFiles, $wgExtensionCredits, $wgAutoloadClasses, $wgHooks, $wgDataValues;

if ( isset( $wgVersion ) && version_compare( $wgVersion, '1.16c', '<' ) ) {
	die( '<b>Error:</b> This version of Validator requires MediaWiki 1.16 or above.' );
}

// Include the DataValues extension if that hasn't been done yet, since it's required for Validator to work.
if ( !defined( 'DATAVALUES_VERSION' ) ) {
	@include_once( __DIR__ . '/../DataValues/DataValues.php' );
}

if ( !defined( 'DATAVALUES_INTERFACES_VERSION' ) ) {
	@include_once( __DIR__ . '/../DataValuesInterfaces/DataValuesInterfaces.php' );
}

if ( !defined( 'DATAVALUES_COMMON_VERSION' ) ) {
	@include_once( __DIR__ . '/../DataValuesCommon/DataValuesCommon.php' );
}

// Attempt to include the DataValues lib if that hasn't been done yet.
// This is the path to the autoloader generated by composer in case of a composer install.
if ( !defined( 'DataValues_VERSION' ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

$dependencies = array(
	'DATAVALUES_VERSION' => 'DataValues',
	'DATAVALUES_INTERFACES_VERSION' => 'DataValuesInterfaces',
	'DATAVALUES_COMMON_VERSION' => 'DataValuesCommon',
);

foreach ( $dependencies as $constant => $name ) {
	if ( !defined( $constant ) ) {
		throw new Exception( 'ParamProcessor depends on the https://www.mediawiki.org/wiki/Extension:' .  $name . ' library.' );
	}
}

unset( $dependencies );


// Register the internationalization file.
$wgExtensionMessagesFiles['Validator'] = __DIR__ . '/Validator.i18n.php';
$wgExtensionMessagesFiles['ValidatorMagic'] = __DIR__ . '/Validator.i18n.magic.php';

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Validator (ParamProcessor)',
	'version' => ParamProcessor_VERSION,
	'author' => array( '[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:ParamProcessor',
	'descriptionmsg' => 'validator-desc',
);

if ( defined( 'MW_PHPUNIT_TEST' ) ) {
	require_once __DIR__ . '/tests/testLoader.php';
}

spl_autoload_register( function ( $className ) {
	$className = ltrim( $className, '\\' );
	$fileName = '';
	$namespace = '';

	if ( $lastNsPos = strripos( $className, '\\' ) ) {
		$namespace = substr( $className, 0, $lastNsPos );
		$className = substr( $className, $lastNsPos + 1 );
		$fileName  = str_replace( '\\', '/', $namespace ) . '/';
	}

	$fileName .= str_replace( '_', '/', $className ) . '.php';

	$namespaceSegments = explode( '\\', $namespace );

	if ( $namespaceSegments[0] === 'ParamProcessor' ) {
		$inTestNamespace = count( $namespaceSegments ) > 1 && $namespaceSegments[1] === 'Tests';

		if ( !$inTestNamespace ) {
			$pathParts = explode( '/', $fileName );
			array_shift( $pathParts );
			$fileName = implode( '/', $pathParts );

			require_once __DIR__ . '/includes/ParamProcessor/' . $fileName;
		}
	}
} );


class_alias( 'ParamProcessor\ParamDefinitionFactory', 'ParamDefinitionFactory' ); // Softly deprecated since 1.0, removal in 1.5
class_alias( 'ParamProcessor\ParamDefinition', 'ParamDefinition' ); // Softly deprecated since 1.0, removal in 1.5
class_alias( 'ParamProcessor\Definition\StringParam', 'StringParam' ); // Softly deprecated since 1.0, removal in 1.5
class_alias( 'ParamProcessor\Definition\StringParam', 'ParamProcessor\StringParam' ); // Softly deprecated since 1.0, removal in 1.5
class_alias( 'ParamProcessor\IParamDefinition', 'IParamDefinition' ); // Softly deprecated since 1.0, removal in 1.5
class_alias( 'ParamProcessor\Definition\DimensionParam', 'DimensionParam' ); // Softly deprecated since 1.0, removal in 1.5

class_alias( 'ParamProcessor\ProcessingError', 'ProcessingError' ); // Deprecated since 1.0, removal in 1.2
class_alias( 'ParamProcessor\Options', 'ValidatorOptions' ); // Deprecated since 1.0, removal in 1.2
class_alias( 'ParamProcessor\IParam', 'IParam' ); // Deprecated since 1.0, removal in 1.2

/**
 * @deprecated since 1.0, removal in 1.3
 */
class Validator extends ParamProcessor\Processor {

	public function __construct() {
		parent::__construct( new ParamProcessor\Options() );
	}

}

// utils
$wgAutoloadClasses['ParserHook']				 	= __DIR__ . '/includes/utils/ParserHook.php';
$wgAutoloadClasses['ValidatorDescribe']		  		= __DIR__ . '/includes/utils/Describe.php';
$wgAutoloadClasses['ValidatorListErrors']			= __DIR__ . '/includes/utils/ListErrors.php';

// Registration of the listerrors parser hooks.
$wgHooks['ParserFirstCallInit'][] = 'ValidatorListErrors::staticInit';

// Registration of the describe parser hooks.
$wgHooks['ParserFirstCallInit'][] = 'ValidatorDescribe::staticInit';

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 1.0
 *
 * @param array $files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][]	= function( array &$files ) {
	// @codeCoverageIgnoreStart
	$directoryIterator = new RecursiveDirectoryIterator( __DIR__ . '/tests/phpunit/' );

	/**
	 * @var SplFileInfo $fileInfo
	 */
	foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
		if ( substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
			$files[] = $fileInfo->getPathname();
		}
	}

	return true;
	// @codeCoverageIgnoreEnd
};

$wgDataValues['mediawikititle'] = 'ParamProcessor\MediaWikiTitleValue';

include_once( __DIR__ . '/config/DefaultConfig.php' );
