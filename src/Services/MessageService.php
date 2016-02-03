<?php
namespace SourceBroker\InstanceManager\Services;

/**
 * Class MessageService
 *
 * Using magical method __call, displayed formatted string.
 *
 * @example $messageService->red('this text is red');
 * @example $messageService->bgBlue('This text has blue background');
 * @example $messageService->bold('This text is bold');
 *
 * @method string default() default(string $text)
 * @method string black() black(string $text)
 * @method string darkGray() darkGray(string $text)
 * @method string blue() blue(string $text)
 * @method string lightBlue() lightBlue(string $text)
 * @method string green() green(string $text)
 * @method string lightGreen() lightGreen(string $text)
 * @method string cyan() cyan(string $text)
 * @method string lightCyan() lightCyan(string $text)
 * @method string red() red(string $text)
 * @method string lightRed() lightRed(string $text)
 * @method string purple() purple(string $text)
 * @method string lightPurple() lightPurple(string $text)
 * @method string brown() brown(string $text)
 * @method string yellow() yellow(string $text)
 * @method string lightGray() lightGray(string $text)
 * @method string white() white(string $text)
 *
 * @method string bold() bold(string $text)
 *
 * @method string bgBlack() bgBlack(string $text)
 * @method string bgRed() bgRed(string $text)
 * @method string bgGreen() bgGreen(string $text)
 * @method string bgYellow() bgYellow(string $text)
 * @method string bgBlue() bgBlue(string $text)
 * @method string bgMagenta() bgMagenta(string $text)
 * @method string bgCyan() bgCyan(string $text)
 * @method string bgLightGray() bgLightGray(string $text)
 */
class MessageService
{

    /**
     * @var array
     */
    protected static $COLORS = array(
        'default' => '0;39',
        'black' => '0;30',
        'darkGray' => '1;30',
        'blue' => '0;34',
        'lightBlue' => '1;34',
        'green' => '0;32',
        'lightGreen' => '1;32',
        'cyan' => '0;36',
        'lightCyan' => '1;36',
        'red' => '0;31',
        'lightRed' => '1;31',
        'purple' => '0;35',
        'lightPurple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'lightGray' => '0;37',
        'white' => '1;37',
    );

    protected static $BACKGROUNDS = array(
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'lightGray' => '47',
    );

    /**
     * @var array
     */
    protected static $FORMATS = array(
        'bold' => '1'
    );

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return string
     */
    public function __call($name, $arguments = array())
    {
        //$name = mb_strtolower($name);
        $message = $arguments[0];

        if (in_array($name, array_keys(self::$COLORS))) {
            return $this->getFormattedString(self::$COLORS[$name], $message);
        }

        if (in_array($name, array_keys(self::$FORMATS))) {
            return $this->getFormattedString(self::$FORMATS[$name], $message);
        }

        if (preg_match('/^bg[A-Z]?/', $name)) {
            $bgName = lcfirst(preg_replace('/^bg/', '', $name));

            return $this->getFormattedString(self::$BACKGROUNDS[$bgName], $message);
        }

        return $message;
    }

    /**
     * @param string $format
     * @param string $string
     *
     * @return string
     */
    protected function getFormattedString($format, $string)
    {
        return "\033[" . $format . "m" . $string . "\033[0m";
    }
}
