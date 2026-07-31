<?php
/**
 * Abstract sniff
 */

// Code copied from https://payton.codes/2017/12/15/creating-sniffs-for-a-phpcs-standard/

namespace JamesCNZ\PHPDocTypesSniff\JamesCNZGeneric\Tests;

// require_once __DIR__ . '/../../vendor/squizlabs/php_codesniffer/autoload.php';
define('PHP_CODESNIFFER_VERBOSITY', 1);
define('PHP_CODESNIFFER_CBF', true);

use PHPUnit\Framework\TestCase;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHP_CodeSniffer\Config;

/**
 * Abstract sniff
 */
abstract class AbstractSniffUnitTest extends TestCase {
    /**
     * Run test
     */
    public function test(): void {
        $reflection = new \ReflectionClass(get_class($this));
        $filePath = $reflection->getFileName();
        assert($filePath != false);
        $directoryPath = dirname($filePath);
        $sniffFile = preg_replace('/\/Tests\//', '/Sniffs/', $filePath);
        assert($sniffFile != false);
        $sniffFile = preg_replace('/UnitTest.php$/', 'Sniff.php', $sniffFile);
        assert($sniffFile != false);
        $sniffFiles = [$sniffFile];
        $config = new Config();
        $ruleset = new Ruleset($config);
        $ruleset->registerSniffs($sniffFiles, [], []);
        $ruleset->populateTokenListeners();
        $fixtureFiles = glob($directoryPath . '/*.inc');
        assert($fixtureFiles != false);
        foreach ($fixtureFiles as $fixtureFile) {
            $fixtureFileNoPath = substr($fixtureFile, strrpos($fixtureFile, '/') + 1);
            $phpcsFile = new LocalFile($fixtureFile, $ruleset, $config);
            $phpcsFile->process();
            $foundErrors = $phpcsFile->getErrors();
            $foundWarnings = $phpcsFile->getWarnings();
            ksort($foundErrors);
            ksort($foundWarnings);
            foreach ($foundErrors as $key => $foundErrorLine) {
                $errCount = 0;
                foreach ($foundErrorLine as $foundErrorCol) {
                    $errCount += count($foundErrorCol);
                }
                $foundErrors[$key] = $errCount;
            }
            foreach ($foundWarnings as $key => $foundWarningLine) {
                $errCount = 0;
                foreach ($foundWarningLine as $foundWarningCol) {
                    $errCount += count($foundWarningCol);
                }
                $foundWarnings[$key] = $errCount;
            }
            $expectedErrors = $this->getErrorList($fixtureFileNoPath);
            $expectedWarnings = $this->getWarningList($fixtureFileNoPath);
            $this->assertEquals($expectedErrors, $foundErrors, "Fixture {$fixtureFileNoPath} errors comparison.");
            $this->assertEquals($expectedWarnings, $foundWarnings, "Fixture {$fixtureFileNoPath} warnings comparison.");
            if (file_exists($fixtureFile . '.fixed')) {
                $phpcsFile->fixer->fixFile();
                $this->assertSame(
                    file_get_contents($fixtureFile . '.fixed'),
                    $phpcsFile->fixer->getContents()
                );
            }
        }
    }

    /**
     * Expected errors
     *
     * @param string $testFile
     * @return array<int, int>
     */
    public abstract function getErrorList($testFile='');

    /**
     * Expected warnings
     *
     * @param string $testFile
     * @return array<int, int>
     */
    public abstract function getWarningList($testFile='');
}
