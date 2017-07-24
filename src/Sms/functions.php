<?php 

if (! function_exists('sms')) {
	/**
	 * Return instance of way2sms
	 * @return Object Way2Sms
	 */
	function sms() {
		return app('sms');
	}
}