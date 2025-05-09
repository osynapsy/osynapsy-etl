<?php

use PHPUnit\Framework\TestCase;
use Osynapsy\Etl\FixedLength\ArrayToFixedLength;

class ArrayToFixedLengthTest extends TestCase
{
    public function testConstructorSetsRecordFormat()
    {
        $recordFormat = [
            'field1' => ['length' => 10, 'type' => 'string'],
            'field2' => ['length' => 5, 'type' => 'integer']
        ];
        $converter = new ArrayToFixedLength($recordFormat);

        // Use reflection to access the protected recordFormat property
        $reflection = new \ReflectionClass($converter);
        $property = $reflection->getProperty('recordFormat');
        $property->setAccessible(true); // Make the protected property accessible
        $actualRecordFormat = $property->getValue($converter); // Get the value

        $this->assertEquals($recordFormat, $actualRecordFormat);
    }

    public function testGenerateWithEmptyRecordsReturnsEmptyString()
    {
        $recordFormat = [
            'field1' => ['length' => 10, 'type' => 'string']
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $records = [];
        $this->assertEquals('', $converter->generate($records));
    }

    public function testGenerateWithEmptyRecordFormatThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Il formato del record non è definito. Forniscilo nel costruttore.');
        $converter = new ArrayToFixedLength([]);
        $records = [['field1' => 'test']];
        $converter->generate($records);
    }

    public function testGenerateWithSingleRecord()
    {
        $recordFormat = [
            'field1' => ['length' => 5, 'type' => 'string'],
            'field2' => ['length' => 5, 'type' => 'string']
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $records = [
            ['field1' => 'abc', 'field2' => 'defg']
        ];
        $expected = "abc  defg \n";
        $this->assertEquals($expected, $converter->generate($records));
    }

    public function testGenerateWithMultipleRecords()
    {
        $recordFormat = [
            'fieldA' => ['length' => 6, 'type' => 'string'],
            'fieldB' => ['length' => 4, 'type' => 'integer', 'field' => 'sourceB']
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $records = [
            ['fieldA' => 'hello', 'sourceB' => 123],
            ['fieldA' => 'world', 'sourceB' => 4567]
        ];
        $expected = "hello 123 \nworld 4567\n";
        $this->assertEquals($expected, $converter->generate($records));
    }

    public function testGenerateWithRequiredFieldMissingThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Campo richiesto "field1" .* non trovato nel record/');
        $recordFormat = [
            'field1' => ['length' => 10, 'type' => 'string', 'required' => true]
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $records = [
            ['anotherField' => 'value']
        ];
        $converter->generate($records);
    }

    public function testGenerateWithRequiredFieldUsingMappingMissingThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Campo richiesto "mappedField" \(mappato da "sourceField"\) non trovato nel record:/');
        $recordFormat = [
            'mappedField' => ['length' => 10, 'type' => 'string', 'required' => true, 'field' => 'sourceField']
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $records = [
            ['anotherSourceField' => 'value']
        ];
        $converter->generate($records);
    }


    public function testCreateRecordString()
    {
        $recordFormat = [
            'field1' => ['length' => 5, 'type' => 'string'],
            'field2' => ['length' => 5, 'type' => 'string']
        ];
        $converter = new ArrayToFixedLength($recordFormat);
        $recordData = ['field1' => 'abc', 'field2' => 'defg'];

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('createRecordString');
        $method->setAccessible(true);

        $expected = "abc  defg \n";
        $this->assertEquals($expected, $method->invokeArgs($converter, [$recordData]));
    }

    /**
     * @dataProvider formatFieldDataProvider
     */
    /*public function testFormatField($rawValue, $rule, $expected)
    {
        $converter = new ArrayToFixedLength([]); // Record format is not needed for formatField

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('formatField');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invokeArgs($converter, [$rawValue, $rule]));
    }*/

    public function formatFieldDataProvider()
    {
        return [
            'string_right_pad' => ['hello', ['length' => 10, 'type' => 'string', 'padding' => ' ', 'paddingDirection' => STR_PAD_RIGHT], 'hello     '],
            'string_left_pad' => ['hello', ['length' => 10, 'type' => 'string', 'padding' => ' ', 'paddingDirection' => STR_PAD_LEFT], '     hello'],
            'string_both_pad' => ['hello', ['length' => 10, 'type' => 'string', 'padding' => ' ', 'paddingDirection' => STR_PAD_BOTH], '  hello   '],
            'string_truncate' => ['this is a long string', ['length' => 10, 'type' => 'string'], 'this is a '],
            'integer_zero_left_pad' => [123, ['length' => 5, 'type' => 'integer'], '00123'],
            'integer_from_float' => [123.45, ['length' => 5, 'type' => 'integer'], '00123'],
            'integer_empty_string' => ['', ['length' => 5, 'type' => 'integer'], '00000'],
            'decimal_zero_left_pad' => [123.45, ['length' => 8, 'type' => 'decimal', 'decimals' => 3], '01234500'], // 123.450 becomes 1234500 with 3 decimals
            'decimal_from_string_comma' => ['123,45', ['length' => 8, 'type' => 'decimal', 'decimals' => 3], '01234500'],
            'decimal_empty_string' => ['', ['length' => 8, 'type' => 'decimal', 'decimals' => 3], '00000000'],
            'money_zero_left_pad' => [123.45, ['length' => 7, 'type' => 'money', 'decimals' => 2], '0012345'], // 123.45 becomes 12345 with 2 decimals
            'money_from_string_comma' => ['123,45', ['length' => 7, 'type' => 'money', 'decimals' => 2], '0012345'],
             'money_empty_string' => ['', ['length' => 7, 'type' => 'money', 'decimals' => 2], '0000000'],
            'flag_boolean_true' => [true, ['length' => 1, 'type' => 'flag'], 'Y'],
            'flag_boolean_false' => [false, ['length' => 1, 'type' => 'flag'], 'N'],
            'flag_string_y' => ['Yes', ['length' => 1, 'type' => 'flag'], 'Y'],
            'flag_string_n' => ['No', ['length' => 1, 'type' => 'flag'], 'N'],
             'flag_string_other' => ['X', ['length' => 1, 'type' => 'flag'], 'X'],
            'string_with_newline_tab_carriage_return' => ["hello\n\t\rworld", ['length' => 20, 'type' => 'string'], 'hello   world       '],
            'string_with_trailing_whitespace' => ['hello   ', ['length' => 10, 'type' => 'string'], 'hello     '],

        ];
    }

    public function testFormatFieldThrowsExceptionOnInvalidLength()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/La regola deve contenere un parametro "length" intero e positivo./');
        $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('formatField');
        $method->setAccessible(true);
        $method->invokeArgs($converter, ['value', ['type' => 'string', 'length' => 0]]);
    }

    public function testFormatFieldThrowsExceptionOnMissingLength()
    {
        $this->expectException(\Exception::class);
         $this->expectExceptionMessageMatches('/La regola deve contenere un parametro "length" intero e positivo./');
        $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('formatField');
        $method->setAccessible(true);
        $method->invokeArgs($converter, ['value', ['type' => 'string']]);
    }


    public function testMbStringPad()
    {
        $converter = new ArrayToFixedLength([]);
        $this->assertEquals('hello     ', $converter->mbStringPad('hello', 10));
        $this->assertEquals('     hello', $converter->mbStringPad('hello', 10, ' ', STR_PAD_LEFT));
        $this->assertEquals('  hello   ', $converter->mbStringPad('hello', 10, ' ', STR_PAD_BOTH));
        $this->assertEquals('你好世界      ', $converter->mbStringPad('你好世界', 10, ' ', STR_PAD_RIGHT, 'UTF-8'));
        $this->assertEquals('this is a ', $converter->mbStringPad('this is a long string', 10)); // Truncate
    }

    public function testMbStringPadThrowsExceptionOnEmptyPadString()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La stringa di padding non può essere vuota.');
        $converter = new ArrayToFixedLength([]);
        $converter->mbStringPad('hello', 10, '');
    }

    public function testMbStringLength()
    {
        $converter = new ArrayToFixedLength([]);
        $this->assertEquals(5, $converter->mbStringLength('hello'));
        $this->assertEquals(4, $converter->mbStringLength('你好世界', 'UTF-8'));
    }

     public function testValidateValueLengthThrowsExceptionWhenTooLong()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/La stringa "this is too long" è troppo lunga \(16 caratteri\). La lunghezza massima consentita è 10 caratteri./');
        $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateValueLength');
        $method->setAccessible(true);
        $method->invokeArgs($converter, ['this is too long', 10]);
    }

    public function testValidateValueLengthDoesNotThrowExceptionWhenNotTooLong()
    {
        $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateValueLength');
        $method->setAccessible(true);
        $method->invokeArgs($converter, ['short', 10]);
        $this->assertTrue(true); // If no exception is thrown, the test passes
    }

     public function testValidateRuleThrowsExceptionOnMissingLength()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/La regola deve contenere un parametro "length" intero e positivo./');
         $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateRule');
        $method->setAccessible(true);
        $method->invokeArgs($converter, [['type' => 'string']]);
    }

    public function testValidateRuleThrowsExceptionOnNonIntegerLength()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/La regola deve contenere un parametro "length" intero e positivo./');
         $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateRule');
        $method->setAccessible(true);
        $method->invokeArgs($converter, [['length' => 10.5, 'type' => 'string']]);
    }

     public function testValidateRuleThrowsExceptionOnNonPositiveLength()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/La regola deve contenere un parametro "length" intero e positivo./');
         $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateRule');
        $method->setAccessible(true);
        $method->invokeArgs($converter, [['length' => 0, 'type' => 'string']]);
    }

    public function testValidateRuleDoesNotThrowExceptionOnValidRule()
    {
         $converter = new ArrayToFixedLength([]);
        $reflection = new \ReflectionClass($converter);
        $method = $reflection->getMethod('validateRule');
        $method->setAccessible(true);
        $method->invokeArgs($converter, [['length' => 10, 'type' => 'string']]);
        $this->assertTrue(true); // If no exception is thrown, the test passes
    }
}
