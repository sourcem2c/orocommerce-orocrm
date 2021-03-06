<?php

namespace Oro\Bridge\CustomerAccount\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\DataFixtures\LoadCustomerData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;

class RestCustomerTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadCustomerData::class]);
    }

    /**
     * @return Customer|null
     */
    private function getDefaultCustomer()
    {
        return $this->getEntityManager()
            ->getRepository(Customer::class)
            ->findOneByName('CustomerUser CustomerUser');
    }

    /**
     * @param string $name
     *
     * @return CustomerGroup|null
     */
    private function getGroup($name)
    {
        return $this->getEntityManager()
            ->getRepository(CustomerGroup::class)
            ->findOneByName($name);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetCustomers()
    {
        $response = $this->cget(['entity' => $this->getEntityType(Customer::class)]);

        $content = self::jsonToArray($response->getContent());
        $defaultCustomer = $this->getDefaultCustomer();
        /** @var Customer $customer1 */
        $customer1 = $this->getReference('customer.1');

        $owner = $defaultCustomer->getOwner();
        $organization = $defaultCustomer->getOrganization();
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$defaultCustomer->getId(),
                    'attributes' => [
                        'name' => $defaultCustomer->getName(),
                        'lifetime' => null,
                    ],
                    'relationships' => [
                        'parent' => ['data' => null,],
                        'children' => [
                            'data' => [
                                [
                                    'type' => 'customers',
                                    'id' => (string)$customer1->getId(),
                                ],
                            ],
                        ],
                        'users' => [
                            'data' => [
                                [
                                    'type' => 'customerusers',
                                    'id' => $defaultCustomer->getUsers()->first()->getId(),
                                ],
                            ],
                        ],
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => (string)$owner->getId()],
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => (string)$organization->getId()],
                        ],
                        'salesRepresentatives' => ['data' => []],
                        'internal_rating' => ['data' => null],
                        'group' => ['data' => null],
                    ],
                ],
                [
                    'type' => 'customers',
                    'id' => (string)$customer1->getId(),
                    'attributes' =>
                        [
                            'name' => 'customer.1',
                            'lifetime' => null,
                        ],
                    'relationships' => [
                        'parent' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => (string)$defaultCustomer->getId(),
                            ],
                        ],
                        'children' => ['data' => [],],
                        'users' => ['data' => [],],
                        'owner' => [
                            'data' => [
                                'type' => 'users',
                                'id' => (string)$owner->getId(),
                            ],
                        ],
                        'organization' => [
                            'data' => [
                                'type' => 'organizations',
                                'id' => (string)$organization->getId(),
                            ],
                        ],
                        'salesRepresentatives' => [
                            'data' => [
                                [
                                    'type' => 'users',
                                    'id' => (string)$owner->getId(),
                                ],
                            ],
                        ],
                        'internal_rating' => [
                            'data' =>
                                [
                                    'type' => 'customerratings',
                                    'id' => 'internal_rating.1_of_5',
                                ],
                        ],
                        'group' => [
                            'data' => [
                                'type' => 'customergroups',
                                'id' => (string)$this->getGroup(LoadGroups::GROUP1)->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertArraySubset($expected, $content);
    }

    public function testGetCustomer()
    {
        /** @var Customer $customer1 */
        $customer = $this->getReference('customer.1');

        $response = $this->get([
            'entity' => $this->getEntityType(Customer::class),
            'id' => (string)$customer->getId()
        ]);

        $content = self::jsonToArray($response->getContent());

        $parent = $this->getDefaultCustomer();
        $owner = $parent->getOwner();
        $organization = $parent->getOrganization();
        $expected = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$customer->getId(),
                'attributes' =>
                    [
                        'name' => 'customer.1',
                        'lifetime' => null,
                    ],
                'relationships' => [
                    'parent' => [
                        'data' => [
                            'type' => 'customers',
                            'id' => (string)$parent->getId(),
                        ],
                    ],
                    'children' => ['data' => [],],
                    'users' => ['data' => [],],
                    'owner' => [
                        'data' => [
                            'type' => 'users',
                            'id' => (string)$owner->getId(),
                        ],
                    ],
                    'organization' => [
                        'data' => [
                            'type' => 'organizations',
                            'id' => (string)$organization->getId(),
                        ],
                    ],
                    'salesRepresentatives' => [
                        'data' => [
                            [
                                'type' => 'users',
                                'id' => (string)$owner->getId(),
                            ],
                        ],
                    ],
                    'internal_rating' => [
                        'data' =>
                            [
                                'type' => 'customerratings',
                                'id' => 'internal_rating.1_of_5',
                            ],
                    ],
                    'group' => [
                        'data' => [
                            'type' => 'customergroups',
                            'id' => (string)$this->getGroup(LoadGroups::GROUP1)->getId(),
                        ],
                    ],
                ],
            ],
        ];
        $this->assertArraySubset($expected, $content);
    }

    public function testGetParentSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.1');
        $parent = $customer->getParent();
        $owner = $customer->getOwner();
        $organization = $customer->getOrganization();

        $response = $this->getSubresource([
            'entity' => $this->getEntityType(Customer::class),
            'id' => $customer->getId(),
            'association' => 'parent'
        ]);
        $content = self::jsonToArray($response->getContent());
        $expected = [
            'data' => [
                'type' => 'customers',
                'id' => (string)$parent->getId(),
                'attributes' => [
                    'name' => $parent->getName(),
                    'lifetime' => null,
                ],
                'relationships' => [
                    'parent' => ['data' => null,],
                    'children' => [
                        'data' => [
                            [
                                'type' => 'customers',
                                'id' => (string)$customer->getId(),
                            ],
                        ],
                    ],
                    'users' => [
                        'data' => [
                            [
                                'type' => 'customerusers',
                                'id' => '1',
                            ],
                        ],
                    ],
                    'owner' => [
                        'data' => ['type' => 'users', 'id' => (string)$owner->getId()],
                    ],
                    'organization' => [
                        'data' => ['type' => 'organizations', 'id' => (string)$organization->getId()],
                    ],
                    'salesRepresentatives' => ['data' => []],
                    'internal_rating' => ['data' => null],
                    'group' => ['data' => null],
                ],
            ],
        ];
        $this->assertArraySubset($expected, $content);
    }

    public function testGetChildrenSubresource()
    {
        /** @var Customer $customer */
        $customer = $this->getDefaultCustomer();

        $response = $this->getSubresource([
            'entity' => $this->getEntityType(Customer::class),
            'id' => $customer->getId(),
            'association' => 'children'
        ]);
        $content = self::jsonToArray($response->getContent());

        $owner = $customer->getOwner();
        $organization = $customer->getOrganization();
        $expected = [
            'data' => [
                [
                    'type' => 'customers',
                    'id' => (string)$this->getReference('customer.1')->getId(),
                    'attributes' =>
                        [
                            'name' => 'customer.1',
                            'lifetime' => null,
                        ],
                    'relationships' => [
                        'parent' => [
                            'data' => [
                                'type' => 'customers',
                                'id' => (string)$customer->getId(),
                            ],
                        ],
                        'children' => ['data' => []],
                        'users' => ['data' => []],
                        'owner' => [
                            'data' => [
                                'type' => 'users',
                                'id' => (string)$owner->getId(),
                            ],
                        ],
                        'organization' => [
                            'data' => [
                                'type' => 'organizations',
                                'id' => (string)$organization->getId(),
                            ],
                        ],
                        'salesRepresentatives' => [
                            'data' => [
                                [
                                    'type' => 'users',
                                    'id' => (string)$owner->getId(),
                                ],
                            ],
                        ],
                        'internal_rating' => [
                            'data' =>
                                [
                                    'type' => 'customerratings',
                                    'id' => 'internal_rating.1_of_5',
                                ],
                        ],
                        'group' => [
                            'data' => [
                                'type' => 'customergroups',
                                'id' => (string)$this->getGroup(LoadGroups::GROUP1)->getId(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertArraySubset($expected, $content);
    }
}
