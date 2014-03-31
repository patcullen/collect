<?php

function action_backup($model, $user) {
	database_backup($user);
	return 'Backup Created';

}











?>