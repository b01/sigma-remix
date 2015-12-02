<?php namespace Kshabazz\SigmaRemix;

/**
 * Class SigmaRemixException
 *
 * @package Kshabazz\SigmaRemix
 */
abstract class SigmaRemixException extends \Exception
{
	const
		UNKNOWN = 1,
		INVALID_CODE = 2;

	/**
	 * @var array Error messages.
	 */
	protected $messages = [
		self::UNKNOWN => 'An unknown error occurred.',
	];

	/**
	 * TemplateException constructor.
	 *
	 * @param string $pCode
	 * @param array|NULL $pData
	 * @param null $pCustomMessage Override built-in messages with your own, will also be used for custom error codes.
	 */
	public function __construct( $pCode, array $pData = NULL, $pCustomMessage = NULL )
	{
		// When custom message is anything other than a string or null.
		if ( !(\is_string($pCustomMessage) && \strlen($pCustomMessage) > 0) && !\is_null($pCustomMessage) )
		{
			throw new \InvalidArgumentException(
				'Third parameter must be a string of length greater than zero or NULL.',
				self::INVALID_CODE
			);
		}

		$message = $this->getMessageByCode( $pCode, $pData, $pCustomMessage );

		parent::__construct( $message, $pCode );
	}

	/**
	 * Returns a textual error message for an error code
	 *
	 * @param integer $pCode error code or another error object for code reuse
	 * @param array $pData additional data to insert into message, processed by vsprintf()
	 * @param string $pCustomMessage Override the built-in message with your own.
	 * @return string error message
	 */
	public function getMessageByCode( $pCode, array $pData = NULL, $pCustomMessage = NULL )
	{
		// Use custom message when set.
		if ( !empty($pCustomMessage) && \strlen($pCustomMessage) > 0 )
		{
			return \vsprintf( $pCustomMessage, $pData );
		}
		// When no entry for code found, return a generic error message.
		if ( !\array_key_exists($pCode, $this->messages) )
		{
			return $this->messages[ self::UNKNOWN ];
		}
		// Parse variables in the error message when present.
		if ( \is_array($pData) )
		{
			return \vsprintf( $this->messages[ $pCode ], $pData );
		}

		return $this->messages[ $pCode ];
	}
}
?>