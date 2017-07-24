<?php namespace ADL;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->app->singleton('sms', function ($app) {
			return $this->app->make(ADL\Sms::class);
		});
	}
}