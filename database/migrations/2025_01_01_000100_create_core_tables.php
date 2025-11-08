<?php

use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('lead_status')->default(LeadStatus::NewLead->value);
            $table->string('source')->nullable();
            $table->json('tags')->nullable();
            $table->string('preferred_language')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->foreignId('assigned_to_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('access_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->string('path');
            $table->string('name');
            $table->string('category')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
        });

        Schema::create('client_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('remind_at');
            $table->string('channel');
            $table->text('message');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->string('direction')->default('outbound');
            $table->string('external_id')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('points_balance', 12, 2)->default(0);
            $table->decimal('lifetime_points', 12, 2)->default(0);
            $table->string('tier')->default('base');
            $table->timestamp('last_redeemed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('points', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->foreignId('referred_client_id')->nullable()->constrained('clients');
            $table->timestamp('redeemed_at')->nullable();
            $table->decimal('bonus_points', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('bonus_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('earn_multiplier', 5, 2)->default(1);
            $table->json('channels')->nullable();
            $table->json('rules')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency')->default('UZS');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->string('number')->unique();
            $table->string('status')->default('sent');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->string('currency')->default('UZS');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('client_addresses');
            $table->foreignId('service_id');
            $table->string('status')->default('scheduled');
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(120);
            $table->foreignId('crew_id')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('recurrence_rule')->nullable();
            $table->timestamp('recurrence_ends_at')->nullable();
            $table->json('start_coordinates')->nullable();
            $table->json('end_coordinates')->nullable();
            $table->timestamps();
        });

        Schema::create('job_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('inspected_by')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('job_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_completed')->default(false);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('job_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('path');
            $table->timestamp('captured_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('job_route_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->json('route_payload')->nullable();
            $table->string('eta_url')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->nullable();
            $table->string('unit');
            $table->decimal('on_hand', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('min_stock_level', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('jobs')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamp('used_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id');
            $table->string('number')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('order_date')->nullable();
            $table->timestamp('expected_at')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->string('status')->default('active');
            $table->json('maintenance_schedule')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('role');
            $table->string('team')->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->string('employment_type');
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('time_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status')->default('present');
            $table->text('notes')->nullable();
            $table->json('geo_coordinates')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->decimal('gross_total', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('staff_id');
            $table->decimal('base_pay', 12, 2);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('staff');
            $table->timestamps();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id');
            $table->date('reviewed_at');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->text('improvement_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id');
            $table->string('course_name');
            $table->string('provider')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id');
            $table->decimal('amount', 12, 2);
            $table->date('issued_at')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('installment', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->decimal('base_price', 12, 2);
            $table->integer('duration_minutes');
            $table->string('pricing_type');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->cascadeOnDelete();
            $table->string('location')->nullable();
            $table->string('client_type')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency')->default('UZS');
            $table->string('unit')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });

        Schema::create('service_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('service_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_bundle_id')->constrained('service_bundles')->cascadeOnDelete();
            $table->foreignId('service_id')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('status')->default('draft');
            $table->text('segment_query')->nullable();
            $table->json('channels');
            $table->timestamp('scheduled_at')->nullable();
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->string('channel');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->string('locale')->default('en');
            $table->timestamps();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('trigger');
            $table->json('actions');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->morphs('auditable');
            $table->json('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('campaign_messages');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('service_bundle_items');
        Schema::dropIfExists('service_bundles');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('staff_loans');
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('time_attendances');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('inventory_usages');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('job_route_plans');
        Schema::dropIfExists('job_photos');
        Schema::dropIfExists('job_checklist_items');
        Schema::dropIfExists('job_checklists');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('bonus_campaigns');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('client_reminders');
        Schema::dropIfExists('client_notes');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('client_addresses');
        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
