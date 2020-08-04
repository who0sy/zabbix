<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CControllerGuiUpdate extends CController {

	protected function checkInput() {
		$themes = array_keys(APP::getThemes());
		$fields = [
			'default_lang' =>			'db config.default_lang|in '.implode(',', array_keys(getLocales())),
			'default_timezone' =>		'required|in '.ZBX_DEFAULT_TIMEZONE.','.implode(',', DateTimeZone::listIdentifiers()).'|db config.default_timezone',
			'default_theme' =>			'required|db config.default_theme|in '.implode(',', $themes),
			'search_limit' =>			'required|db config.search_limit|ge 1|le 999999',
			'max_in_table' =>			'required|db config.max_in_table|ge 1|le 99999',
			'server_check_interval' =>	'required|db config.server_check_interval|in 0,'.SERVER_CHECK_INTERVAL
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			switch ($this->GetValidationError()) {
				case self::VALIDATION_ERROR:
					$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
						->setArgument('action', 'gui.edit')
						->getUrl()
					);
					$response->setFormData($this->getInputAll());
					CMessageHelper::setErrorTitle(_('Cannot update configuration'));
					$this->setResponse($response);
					break;

				case self::VALIDATION_FATAL_ERROR:
					$this->setResponse(new CControllerResponseFatal());
					break;
			}
		}

		return $ret;
	}

	protected function checkPermissions() {
		return ($this->getUserType() == USER_TYPE_SUPER_ADMIN);
	}

	protected function doAction() {
		$data = [];

		$this->getInputs($data, ['default_lang', 'default_timezone', 'default_theme', 'search_limit', 'max_in_table',
			'server_check_interval'
		]);

		DBstart();
		$result = update_config($data);
		$result = DBend($result);

		$response = new CControllerResponseRedirect((new CUrl('zabbix.php'))
			->setArgument('action', 'gui.edit')
			->getUrl()
		);

		if ($result) {
			CMessageHelper::setSuccessTitle(_('Configuration updated'));
		}
		else {
			$response->setFormData($this->getInputAll());
			CMessageHelper::setErrorTitle(_('Cannot update configuration'));
		}

		$this->setResponse($response);
	}
}
