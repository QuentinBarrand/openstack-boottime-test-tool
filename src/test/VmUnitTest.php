<?php

class VmUnitTest {
	public static function newVmUnitTest() {
		Vm::newVm(1, 1);
	}


	public static function generateUserDataUnitTest() {
		Vm::generateUserData(1, 1);
	}


	public static function setReadyStateUnitTest() {
		Vm::setReadyState(1, 1, 1);
		Vm::setReadyState(1, 1, 2);
	}
}