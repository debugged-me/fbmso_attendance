<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Email — extends CI_Email with a public disconnect() method.
 *
 * Why this exists:
 *   CI_Email::_send_with_smtp() returns FALSE on auth failure WITHOUT calling
 *   _smtp_end(), leaving the TCP socket open. _smtp_connect() then reuses that
 *   stale socket on the next send() — even if initialize() was called with a
 *   different SMTP host/user/pass. That causes credentials for one server to
 *   be sent to a different server, producing "535 5.7.8 Authentication failed"
 *   against the wrong host.
 *
 *   The fbmso mail queue calls disconnect() before every initialize() so each
 *   delivery profile gets a guaranteed-fresh connection.
 */
class MY_Email extends CI_Email
{
	/**
	 * Force-close any open SMTP socket and clear the auth flag, so the next
	 * initialize()+send() opens a brand-new connection to the configured host.
	 *
	 * Safe to call when no connection is open.
	 *
	 * @return	void
	 */
	public function disconnect()
	{
		if (is_resource($this->_smtp_connect))
		{
			// Best-effort QUIT; ignore errors — the socket may be in a bad state.
			@fwrite($this->_smtp_connect, 'QUIT' . $this->newline);
			@fclose($this->_smtp_connect);
		}
		$this->_smtp_connect = NULL;
		$this->_smtp_auth    = FALSE;
	}
}
