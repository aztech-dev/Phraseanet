<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

/**
 * Test class for Fields.
 * Generated by PHPUnit on 2012-01-11 at 18:25:03.
 */
class ControllerFieldsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

  /**
   * As controllers use WebTestCase, it requires a client
   */
  protected $client;

  /**
   * If the controller tests require some records, specify it her
   *
   * For example, this will loacd 2 records
   * (self::$record_1 and self::$record_2) :
   *
   * $need_records = 2;
   *
   */
  protected static $need_records = false;

  /**
   * The application loader
   */
  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Admin.php';
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  /**
   * Default route test
   */
  public function testCheckMulti()
  {
    $appbox = \appbox::get_instance();
    $databox = array_shift($appbox->get_databoxes());

    $field = \databox_field::create($databox, "test" . time());
    $source = $field->get_source();

    $this->client->request("GET", "/fields/checkmulti/", array(
        'souce' => $source, 'multi' => 'false'));

    $response = $this->client->getResponse();
    $this->assertEquals("application/json", $response->headers->get("content-type"));
    $datas = json_decode($response->getContent());
    $this->assertTrue(is_object($datas));
    $this->assertTrue(!!$datas->result);
    $this->assertEquals($field->is_multi(), !!$datas->is_multi);
    $field->delete();
  }

  public function testCheckReadOnly()
  {
    $appbox = \appbox::get_instance();
    $databox = array_shift($appbox->get_databoxes());

    $field = \databox_field::create($databox, "test" . time());
    $source = $field->get_source();

    $this->client->request("GET", "/fields/checkreadonly/", array(
        'souce' => $source, 'readonly' => 'false'));

    $response = $this->client->getResponse();
    $this->assertEquals("application/json", $response->headers->get("content-type"));
    $datas = json_decode($response->getContent());
    $this->assertTrue(is_object($datas));
    $this->assertTrue(!!$datas->result);
    $this->assertEquals($field->is_readonly(), !!$datas->is_readonly);

    $field->delete();
  }

}
