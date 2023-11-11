<?php

declare(strict_types=1);

namespace LaminasTest\Log;

use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Mock as MockWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

use function array_flip;
use function array_map;

/**
 * @coversDefaultClass \Laminas\Log\PsrLoggerAdapter
 * @covers ::<!public>
 */
class PsrLoggerAdapterTest extends TestCase
{
    /** @var array */
    protected $psrPriorityMap = [
        LogLevel::EMERGENCY => Logger::EMERG,
        LogLevel::ALERT     => Logger::ALERT,
        LogLevel::CRITICAL  => Logger::CRIT,
        LogLevel::ERROR     => Logger::ERR,
        LogLevel::WARNING   => Logger::WARN,
        LogLevel::NOTICE    => Logger::NOTICE,
        LogLevel::INFO      => Logger::INFO,
        LogLevel::DEBUG     => Logger::DEBUG,
    ];
    private MockWriter $mockWriter;

    /**
     * Provides logger for LoggerInterface compat tests
     *
     * @return PsrLoggerAdapter
     */
    public function getLogger()
    {
        $this->mockWriter = new MockWriter();
        $logger           = new Logger();
        $logger->addProcessor('psrplaceholder');
        $logger->addWriter($this->mockWriter);
        return new PsrLoggerAdapter($logger);
    }

    /**
     * This must return the log messages in order.
     *
     * The simple formatting of the messages is: "<LOG LEVEL> <MESSAGE>".
     *
     * Example ->error('Foo') would yield "error Foo".
     *
     * @return string[]
     */
    public function getLogs()
    {
        $prefixMap = array_flip($this->psrPriorityMap);
        return array_map(function ($event) use ($prefixMap) {
            $prefix = $prefixMap[$event['priority']];
            return $prefix . ' ' . $event['message'];
        }, $this->mockWriter->events);
    }

    protected function tearDown(): void
    {
        unset($this->mockWriter);
    }

    /**
     * @covers ::__construct
     * @covers ::getLogger
     */
    public function testSetLogger(): void
    {
        $logger = new Logger();

        $adapter = new PsrLoggerAdapter($logger);
        $this->assertSame($logger, $adapter->getLogger());
    }

    /**
     * @covers ::log
     * @dataProvider logLevelsToPriorityProvider
     */
    public function testPsrLogLevelsMapsToPriorities($logLevel, $priority): void
    {
        $message = 'foo';
        $context = ['bar' => 'baz'];

        $logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['log'])
            ->getMock();
        $logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($priority),
                $this->equalTo($message),
                $this->equalTo($context)
            );

        $adapter = new PsrLoggerAdapter($logger);
        $adapter->log($logLevel, $message, $context);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function logLevelsToPriorityProvider()
    {
        $return = [];
        foreach ($this->psrPriorityMap as $level => $priority) {
            $return[] = [$level, $priority];
        }
        return $return;
    }

    public function testThrowsOnInvalidLevel()
    {
        $logger = $this->getLogger();
        $this->expectException(InvalidArgumentException::class);
        $logger->log('invalid level', 'Foo');
    }
}
