<?php

namespace RabbitEvents\Bundle\Tests\Message\Serializer;

use Mockery;
use RabbitEvents\Bundle\Tests\TestCase;
use RabbitEvents\Bundle\Contract\ContentType;
use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Message\Serializer\Registry;
use RabbitEvents\Bundle\Exception\UnsupportedContentTypeException;

class RegistryTest extends TestCase
{
    private function mockSerializer(string $contentType)
    {
        $type = Mockery::mock(ContentType::class);
        $type->shouldReceive('__toString')->andReturn($contentType);
        $type->shouldReceive('getValue')->andReturn($contentType);

        $serializer = Mockery::mock(Serializer::class);
        $serializer->shouldReceive('contentType')->andReturn($type);
        $serializer->shouldReceive('canSerialize')->andReturn(false);

        return $serializer;
    }

    public function testRegisterAndGet()
    {
        $serializer = $this->mockSerializer('application/json');

        $registry = new Registry($serializer);

        $this->assertSame($serializer, $registry->get('application/json'));
    }

    public function testGetDefault()
    {
        $s1 = $this->mockSerializer('application/json');

        $registry = new Registry($s1);

        $this->assertSame($s1, $registry->getDefault());
    }

    public function testGetUnsupportedThrowsException()
    {
        $s1 = $this->mockSerializer('application/json');

        $registry = new Registry($s1);

        $this->expectException(UnsupportedContentTypeException::class);
        $registry->get('application/xml');
    }
}
