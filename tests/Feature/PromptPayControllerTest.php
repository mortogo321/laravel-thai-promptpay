<?php

namespace Mortogo321\LaravelThaiPromptPay\Tests\Feature;

use Mortogo321\LaravelThaiPromptPay\Tests\TestCase;

class PromptPayControllerTest extends TestCase
{
    // ===========================================
    // Generate Endpoint Tests
    // ===========================================

    public function test_generate_endpoint_returns_qr_code(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'amount' => 100.50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'identifier' => '0812345678',
                'type' => 'mobile',
                'amount' => 100.50,
            ])
            ->assertJsonStructure([
                'success',
                'qr_code',
                'identifier',
                'type',
                'amount',
            ]);

        $this->assertStringStartsWith('data:image/png;base64,', $response->json('qr_code'));
    }

    public function test_generate_endpoint_without_amount(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'amount' => null,
            ]);
    }

    public function test_generate_endpoint_with_custom_size(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'size' => 500,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_generate_endpoint_validates_required_identifier(): void
    {
        $response = $this->postJson('/promptpay/generate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_generate_endpoint_validates_identifier_max_length(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => str_repeat('0', 25),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_generate_endpoint_validates_amount_numeric(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'amount' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_generate_endpoint_validates_amount_min(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'amount' => -100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_generate_endpoint_validates_size_range(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'size' => 50, // below minimum
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['size']);

        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '0812345678',
            'size' => 2000, // above maximum
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['size']);
    }

    public function test_generate_endpoint_returns_422_for_invalid_identifier(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '12345', // invalid length
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ===========================================
    // Payload Endpoint Tests
    // ===========================================

    public function test_payload_endpoint_returns_payload_string(): void
    {
        $response = $this->postJson('/promptpay/payload', [
            'identifier' => '0812345678',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'identifier' => '0812345678',
            ])
            ->assertJsonStructure([
                'success',
                'payload',
                'identifier',
                'amount',
            ]);

        $this->assertMatchesRegularExpression('/^00/', $response->json('payload'));
    }

    public function test_payload_endpoint_with_amount(): void
    {
        $response = $this->postJson('/promptpay/payload', [
            'identifier' => '0812345678',
            'amount' => 50.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'amount' => 50.00,
            ]);

        $this->assertStringContainsString('50.00', $response->json('payload'));
    }

    public function test_payload_endpoint_validates_required_identifier(): void
    {
        $response = $this->postJson('/promptpay/payload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    // ===========================================
    // Download Endpoint Tests
    // ===========================================

    public function test_download_endpoint_returns_png_file(): void
    {
        $response = $this->post('/promptpay/download', [
            'identifier' => '0812345678',
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');

        $this->assertStringContainsString(
            'attachment; filename="promptpay-',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_download_endpoint_with_amount(): void
    {
        $response = $this->post('/promptpay/download', [
            'identifier' => '0812345678',
            'amount' => 100,
        ]);

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_download_endpoint_validates_required_identifier(): void
    {
        $response = $this->postJson('/promptpay/download', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    // ===========================================
    // Different Identifier Type Tests
    // ===========================================

    public function test_generate_with_national_id(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '1234567890123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'tax_id',
            ]);
    }

    public function test_generate_with_ewallet(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '123456789012345',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'ewallet',
            ]);
    }

    public function test_generate_with_formatted_mobile(): void
    {
        $response = $this->postJson('/promptpay/generate', [
            'identifier' => '081-234-5678',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'mobile',
            ]);
    }
}
