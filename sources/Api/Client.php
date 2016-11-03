<?php

namespace IPS\teamspeak\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Client extends \IPS\teamspeak\Api
{
	const REGULAR_CLIENT = 0;
	const QUERY_CLIENT = 1;

	/**
	 * Only here for auto-complete.
	 *
	 * @return Client
	 */
	public static function i()
	{
		return parent::i();
	}

	/**
	 * Get list of all connected clients.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getClientList()
	{
		$ts = static::getInstance();
		$clientList = $ts->clientList();

		if ( $ts->succeeded( $clientList ) )
		{
			$clientList = $ts->getElement( 'data', $clientList );

			return $this->prepareClientList( $clientList );
		}

		throw new \Exception(); //TODO
	}

	/**
	 * Kick a client from the server.
	 *
	 * @param int $clientId
	 * @param string $message
	 * @return bool
	 * @throws \Exception
	 */
	public function kick( $clientId, $message = "" )
	{
		$ts = static::getInstance();

		$kickInfo = $ts->clientKick( $clientId, "server", $message );

		if ( $ts->succeeded( $kickInfo ) )
		{
			return true;
		}

		throw new \Exception( $this->arrayToString( $ts->getElement( 'errors', $kickInfo ) ) );
	}

	/**
	 * Poke client with given message.
	 *
	 * @param int $clientId
	 * @param string $message
	 * @return bool
	 * @throws \Exception
	 */
	public function poke( $clientId, $message )
	{
		$ts = static::getInstance();

		$pokeInfo = $ts->clientPoke( $clientId, $message );

		if ( $ts->succeeded( $pokeInfo ) )
		{
			return true;
		}

		throw new \Exception( $this->arrayToString( $ts->getElement( 'errors', $pokeInfo ) ) );
	}

	/**
	 * Mass poke clients with given message.
	 *
	 * @param string $message
	 * @param int|array $groups
	 * @return bool
	 * @throws \Exception
	 */
	public function masspoke( $message, $groups )
	{
		$ts = static::getInstance();
		$temp = $ts->clientList( '-groups' );

		if ( $ts->succeeded( $temp ) )
		{
			$clients = $ts->getElement( 'data', $temp );

			foreach ( $clients as $client )
			{
				/* Skip non-regular clients */
				if ( $client['client_type'] != static::REGULAR_CLIENT )
				{
					continue;
				}

				$clientGroups = explode( ',', $client['client_servergroups'] );

				if ( $groups == -1 || ( is_array( $groups ) && !empty( array_intersect( $groups, $clientGroups ) ) ) )
				{
					$ts->clientPoke( $client['clid'], $message );
				}
			}

			return true;
		}

		throw new \Exception( $this->arrayToString( $ts->getElement( 'errors', $temp ) ) );
	}

	/**
	 * Ban client from the server.
	 *
	 * @param int $clientId
	 * @param int|\IPS\DateTime $banTime
	 * @param string $reason
	 * @return bool
	 * @throws \Exception
	 */
	public function ban( $clientId, $banTime, $reason )
	{
		$ts = static::getInstance();

		if ( $banTime !== 0 )
		{
			$banTime = $banTime->getTimestamp() - time();
		}

		$banInfo = $ts->banClient( $clientId, $banTime, $reason );

		if ( $ts->succeeded( $banInfo ) )
		{
			return true;
		}

		throw new \Exception( $this->arrayToString( $ts->getElement( 'errors', $banInfo ) ) );
	}

	/**
	 * Only return regular clients.
	 *
	 * @param array $clientList
	 * @return array
	 */
	protected function prepareClientList( array $clientList )
	{
		foreach ( $clientList as $id => $client )
		{
			if ( $client['client_type'] != static::REGULAR_CLIENT )
			{
				unset( $clientList[$id] );
			}
		}

		return $clientList;
	}
}