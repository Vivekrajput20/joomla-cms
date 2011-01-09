<?php
/**
 * @version		$Id$
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once JPATH_BASE.'/libraries/joomla/event/dispatcher.php';
require_once JPATH_BASE.'/tests/unit/JoomlaTestCase.php';
require_once dirname(__FILE__).'/dispatcherSamples.php';
require_once dirname(__FILE__).'/JDispatcherStub.php';

/**
 * Test class for JDispatcher.
 * Generated by PHPUnit on 2009-10-09 at 14:07:13.
 */
class JDispatcherTest extends JoomlaTestCase {
	/**
	 * @var	JDispatcher
	 * @access protected
	 */
	protected $object;

	protected static $errors;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp() {
		$this->object = new JDispatcher;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown() {
	}

	/**
	 * Tests that we get a JDispatcher object
	 */
	public function testGetInstance() {
		$dispatcher = JDispatcher::getInstance();

		$this->assertThat(
			$dispatcher,
			$this->isInstanceOf('JDispatcher')
		);
	}

	/**
	 * Test register when used with a function to handle an event
	 */
	public function testRegisterWithFunction() {
		$dispatcher = $this->getMock('JDispatcher', array('attach'));
		$dispatcher->expects($this->once())
			->method('attach')
			->with($this->equalTo(array('event' => 'testEvent', 'handler' => 'myTestHandler')));

		$dispatcher->register('testEvent', 'myTestHandler');
	}

	/**
	 * Test register when used with a class to handle an event
	 */
	public function testRegisterWithClass() {
		$dispatcher = $this->getMock('JDispatcher', array('attach'));

		// attach should get called once with an object of our observer class
		$dispatcher->expects($this->once())
			->method('attach')
			->with($this->isInstanceOf('myTestClassHandler'));

		// we reset $observables so we have a known state
		myTestClassHandler::$observables = array();

		// we perform out register
		$dispatcher->register('testEvent', 'myTestClassHandler');

		// we assert that we were registered with a JDispatcher
		$this->assertThat(
			myTestClassHandler::$observables[0],
			$this->isInstanceOf('JDispatcher')
		);

		// and that we were instantiated only once
		$this->assertThat(
			count(myTestClassHandler::$observables[0]),
			$this->equalTo(1)
		);
	}


	static function errorCallback( &$error )
	{
		self::$errors[] = $error;
	}


	/**
	 * Test register when our handler is non existent and make sure we get a warning
	 */
	public function testRegisterWarning() {
		self::$errors = array();
		$this->saveErrorHandlers();
		$this->setErrorCallback('JDispatcherTest');

		$dispatcher = $this->getMock('JDispatcher', array('attach'));

		$dispatcher->register('myEvent', 'thisFunctionDoesNotExist');

		$this->assertThat(
			self::$errors[0]->getMessage(),
			$this->equalTo('JLIB_EVENT_ERROR_DISPATCHER')
		);

		$this->assertThat(
			count(self::$errors[0]->getMessage()),
			$this->equalTo(1)
		);

		$this->setErrorHandlers($this->savedErrorState);
	}

	/**
	 * Trigger an event that will be handled by a function
	 */
	public function testTriggerWithFunction() {
		// instantiate our dispatcher
		$dispatcher = new JDispatcherStub;

		// setup our state
		$methods = array('myevent' => array(0));
		$observers = array(array('event' => 'myEvent', 'handler' => 'myTestHandler'));

		$dispatcher->setMethods($methods);
		$dispatcher->setObservers($observers);

		// perform our trigger
		$this->assertThat(
			$dispatcher->trigger('myEvent'),
			$this->equalTo(array(12345))
		);

		$this->assertThat(
			myTestHandler(true),
			$this->equalTo(array(array()))
		);

		// perform our trigger with parameters
		$this->assertThat(
			$dispatcher->trigger('myEvent', array('hello', 'goodbye')),
			$this->equalTo(array('goodbye'))
		);

		$this->assertThat(
			myTestHandler(true),
			$this->equalTo(array(array('hello', 'goodbye')))
		);

	}

	/**
	 * Trigger an event and pass it arguments that are not an array (to test that they get cast properly)
	 */
	public function testTriggerArgsNotArray() {
		// instantiate our dispatcher
		$dispatcher = new JDispatcherStub;

		// setup our state
		$methods = array('myevent' => array(0));
		$observers = array(array('event' => 'myEvent', 'handler' => 'myTestHandler'));

		$dispatcher->setMethods($methods);
		$dispatcher->setObservers($observers);

		// perform our trigger
		$this->assertThat(
			$dispatcher->trigger('myEvent', 'This is not an array'),
			$this->equalTo(array(12345))
		);

		$this->assertThat(
			myTestHandler(true),
			$this->equalTo(array(array('This is not an array')))
		);
	}

	/**
	 * Trigger an event that will be handled by an object
	 */
	public function testTriggerWithClass() {
		// instantiate our dispatcher
		$dispatcher = new JDispatcherStub;

		// create a mock observer that will expect its update method to be called with the event name
		$mockObserver = $this->getMock('TestObserver', array('update'));
		$mockObserver->expects($this->once())
					->method('update')
					->with(array('event' => 'myevent'))
					->will($this->returnValue('testTriggerClass'));

		// setup our state
		$methods = array('myevent' => array(0));
		$observers = array($mockObserver);

		$dispatcher->setMethods($methods);
		$dispatcher->setObservers($observers);

		// perform our trigger and test the result
		$this->assertThat(
			$dispatcher->trigger('myEvent'),
			$this->equalTo(array('testTriggerClass'))
		);
	}

	/**
	 * Trigger an event that will not be handled because an observer is missing
	 * (i.e. the entry is registered in methods, but the actual observer doesn't exist in the array)
	 */
	public function testTriggerWithNonPresentObserver() {
		// instantiate our dispatcher
		$dispatcher = new JDispatcherStub;

		// setup our state - we look for an observer at index 3
		$methods = array('myevent' => array(3));
		$observers = array();

		$dispatcher->setMethods($methods);
		$dispatcher->setObservers($observers);

		// perform our trigger and test the result
		$this->assertThat(
			$dispatcher->trigger('myEvent'),
			$this->equalTo(array())
		);
	}

}
