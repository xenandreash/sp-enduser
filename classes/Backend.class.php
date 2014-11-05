<?php

class Backend
{
	/// Does the backend support loading history?
	public function supportsHistory() { return false; }
	
	/// Does the backend support loading the queue?
	public function supportsQueue() { return false; }
	
	/// Does the backend support loading quarantines?
	public function supportsQuarantine() { return false; }
	
	/// Loads the mail history
	public function loadMailHistory($search, $size, &$errors = array()) { return null; }
	
	/// Loads the mail queue
	public function loadMailQueue($search, $size, &$errors = array()) { return null; }
}
