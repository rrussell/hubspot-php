<?php
namespace Fungku\HubSpot\Tests\Integration\Api;

use Fungku\HubSpot\Api\Companies;
use Fungku\HubSpot\Api\Contacts;
use Fungku\HubSpot\Api\Deals;
use Fungku\HubSpot\Http\Client;

/**
 * Class DealsTest
 * @package Fungku\HubSpot\Tests\Integration\Api
 * @group deals
 */
class DealsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Deals
     */
    private $deals;

    public function setUp()
    {
        parent::setUp();
        $this->deals = new Deals('demo', new Client());
        sleep(1);
    }

    /*
     * Lots of tests need an existing object to modify.
     */
    private function createDeal()
    {
        sleep(1);

        $response = $this->deals->create([
            "properties" => [
                [
                    "value" => "Cool Deal",
                    "name" => "dealname"
                ],
                [
                    "value" => "60000",
                    "name" => "amount"
                ],
            ]
        ]);

        return $response;
    }

    /**
     * @return int
     */
    private function createCompany()
    {
        $companies = new Companies('demo', new Client());

        return $companies->create(['name' => 'name', 'value' => 'dl_test_company'.uniqid()])->companyId;
    }

    private function createContact()
    {
        $contacts = new Contacts('demo', new Client());

        $response = $contacts->create([
            ['property' => 'email', 'value' => 'dl_test_contact'.uniqid().'@hubspot.com']
        ]);

        return $response->vid;
    }

    /**
     * @test
     */
    public function create()
    {
        $response = $this->createDeal();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('Cool Deal', $response['properties']['dealname']['value']);
        $this->assertSame('60000', $response['properties']['amount']['value']);
    }

    /**
     * @test
     */
    public function find()
    {
        $response = $this->createDeal();
        $id = $response['dealId'];

        //Should not be able to find a deal after it was deleted
        $response = $this->deals->getById($id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('Cool Deal', $response['properties']['dealname']['value']);
        $this->assertSame('60000', $response['properties']['amount']['value']);
    }

    /**
     * @test
     */
    public function update()
    {
        $response = $this->createDeal();

        $id = $response->dealId;

        $response = $this->deals->update($id, [
            "properties" => [
                [
                    "name"  => "amount",
                    "value" => "70000",
                ],
            ],
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('70000', $response['properties']['amount']['value']);
    }

    /**
     * @test
     */
    public function delete()
    {
        $response = $this->createDeal();
        $id = $response['dealId'];

        $response = $this->deals->delete($id);
        $this->assertEquals(204, $response->getStatusCode());

        //Should not be able to find a deal after it was deleted
        $response = $this->deals->getById($id);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function recentlyCreated()
    {
        //Create 4 deals
        for ($i=1; $i<=4; ++$i) {
            $this->createDeal();
        }

        $response = $this->deals->getRecentlyCreated([
            'offset' => 1,
            'count' => 3,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(3, count($response['results']));
    }

    /**
     * @test
     */
    public function recentlyModified()
    {
        $response = $this->deals->getRecentlyModified([
            'offset' => 1,
            'count' => 2,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(2, count($response['results']));
    }

    /**
     * @group now
     */
    public function associateWithCompany()
    {
        $dealId = $this->createDeal()->dealId;

        $firstCompanyId = $this->createCompany();
        $secondCompanyId = $this->createCompany();
        $thirdCompanyId = $this->createCompany();

        $response = $this->deals->associateWithCompany($dealId, [
            $firstCompanyId,
            $secondCompanyId,
            $thirdCompanyId
        ]);
        $this->assertSame(204, $response->getStatusCode());

        //Check what was associated
        $response = $this->deals->getById($dealId);

        $associatedCompanies = $response->associations->associatedCompanyIds;
        $expectedAssociatedCompanies = [$firstCompanyId, $secondCompanyId, $thirdCompanyId];

        //sorting as order is not predicatable
        sort($associatedCompanies);
        sort($expectedAssociatedCompanies);

        $this->assertEquals($expectedAssociatedCompanies, $associatedCompanies);

        //Now disassociate
        $response = $this->deals->disassociateFromCompany($dealId, [
            $firstCompanyId,
            $thirdCompanyId,
        ]);
        $this->assertSame(204, $response->getStatusCode());

        //Ensure that only one associated company left
        $response = $this->deals->getById($dealId);
        $this->assertSame([$secondCompanyId], $response->associations->associatedCompanyIds);
    }

    /**
     * @test
     */
    public function associateWithContact()
    {
        $dealId = $this->createDeal()->dealId;

        $firstContactId = $this->createContact();
        $secondContactId = $this->createContact();
        $thirdContactId = $this->createContact();

        $response = $this->deals->associateWithContact($dealId, [
            $firstContactId,
            $secondContactId,
            $thirdContactId
        ]);
        $this->assertSame(204, $response->getStatusCode());

        //Check what was associated
        $response = $this->deals->getById($dealId);

        $associatedContacts = $response->associations->associatedVids;
        $expectedAssociatedContacts = [$firstContactId, $secondContactId, $thirdContactId];

        //sorting as order is not predicatable
        sort($associatedContacts);
        sort($expectedAssociatedContacts);

        $this->assertEquals($expectedAssociatedContacts, $associatedContacts);

        //Now disassociate
        $response = $this->deals->disassociateFromContact($dealId, [
            $firstContactId,
            $thirdContactId,
        ]);
        $this->assertSame(204, $response->getStatusCode());

        //Ensure that only one associated contact left
        $response = $this->deals->getById($dealId);
        $this->assertSame([$secondContactId], $response->associations->associatedVids);
    }
}
