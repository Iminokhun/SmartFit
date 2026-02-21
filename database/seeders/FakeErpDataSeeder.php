<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subscription;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FakeErpDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Customer::count() >= 40 && CustomerSubscription::count() >= 80) {
            return;
        }

        $faker = fake();

        $trainerRole = Role::firstOrCreate(['name' => 'trainer']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'cashier']);

        $activityCategories = collect([
            'Group Classes',
            'Fitness',
            'Mind & Body',
            'Combat',
        ])->map(fn (string $name) => ActivityCategory::firstOrCreate(['name' => $name]));

        $activities = collect([
            ['Yoga', 'Mind & Body'],
            ['Pilates', 'Mind & Body'],
            ['Boxing', 'Combat'],
            ['CrossFit', 'Fitness'],
            ['Zumba', 'Group Classes'],
            ['Stretching', 'Fitness'],
        ])->map(function (array $item) use ($activityCategories) {
            [$name, $categoryName] = $item;
            $category = $activityCategories->firstWhere('name', $categoryName);

            return Activity::firstOrCreate(
                ['name' => $name],
                ['activity_category_id' => $category?->id, 'icon' => null],
            );
        });

        $halls = collect([
            'Hall A',
            'Hall B',
            'Hall C',
            'Hall D',
        ])->map(fn (string $name) => Hall::firstOrCreate(['name' => $name], ['description' => null]));

        $staff = collect(range(1, 8))->map(function () use ($faker, $trainerRole) {
            return Staff::create([
                'role_id' => $trainerRole->id,
                'user_id' => null,
                'full_name' => $faker->name(),
                'specialization' => $faker->randomElement(['Yoga', 'Boxing', 'CrossFit', 'Pilates', 'Zumba']),
                'experience_years' => $faker->numberBetween(1, 10),
                'phone' => '+9989' . $faker->numerify('########'),
                'email' => $faker->unique()->safeEmail(),
                'photo' => null,
                'status' => $faker->randomElement(['active', 'active', 'active', 'inactive', 'vacation']),
                'salary_type' => $faker->randomElement(['fixed', 'percent', 'per_session']),
                'salary' => $faker->randomFloat(2, 2000000, 9000000),
            ]);
        });

        $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($staff as $trainer) {
            Shift::create([
                'staff_id' => $trainer->id,
                'days_of_week' => collect($weekDays)->shuffle()->take(4)->values()->all(),
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
            ]);
        }

        $subscriptions = collect(range(1, 12))->map(function () use ($faker, $activities) {
            $duration = $faker->randomElement([30, 30, 60, 90]);
            $visitsLimit = $faker->randomElement([8, 12, 16, 24, null]);
            $price = $faker->randomElement([300000, 450000, 600000, 900000, 1200000]);
            $discount = $faker->randomElement([0, 0, 5, 10, 15]);

            return Subscription::create([
                'activity_id' => $activities->random()->id,
                'name' => trim(($visitsLimit ? $visitsLimit . ' visits ' : 'Unlimited ') . $duration . 'd'),
                'description' => $faker->sentence(10),
                'duration_days' => $duration,
                'price' => $price,
                'visits_limit' => $visitsLimit,
                'discount' => $discount,
            ]);
        });

        $customers = collect(range(1, 90))->map(function () use ($faker) {
            return Customer::create([
                'full_name' => $faker->name(),
                'birth_date' => $faker->dateTimeBetween('-50 years', '-16 years')->format('Y-m-d'),
                'phone' => '+9989' . $faker->unique()->numerify('########'),
                'email' => $faker->unique()->safeEmail(),
                'gender' => $faker->randomElement(['male', 'female']),
                'photo' => null,
                'status' => $faker->randomElement(['active', 'active', 'active', 'inactive', 'blocked']),
            ]);
        });

        $customerSubscriptions = collect(range(1, 170))->map(function () use ($faker, $customers, $subscriptions) {
            $customer = $customers->random();
            $subscription = $subscriptions->random();

            $start = Carbon::now()->subDays($faker->numberBetween(0, 180))->startOfDay();
            $end = (clone $start)->addDays((int) $subscription->duration_days);
            $remainingVisits = $subscription->visits_limit === null
                ? null
                : $faker->numberBetween(0, (int) $subscription->visits_limit);

            $status = 'active';
            if ($end->isPast()) {
                $status = 'expired';
            } elseif ($faker->boolean(8)) {
                $status = $faker->randomElement(['frozen', 'cancelled']);
            }

            return CustomerSubscription::create([
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'remaining_visits' => $remainingVisits,
                'status' => $status,
                'paid_amount' => 0,
                'debt' => 0,
                'payment_status' => 'unpaid',
            ]);
        });

        foreach ($customerSubscriptions as $customerSubscription) {
            $finalPrice = round($customerSubscription->finalPrice(), 2);
            if ($finalPrice <= 0) {
                continue;
            }

            $scenario = $faker->randomElement(['full', 'split', 'unpaid', 'pending', 'failed']);
            $createdAt = Carbon::parse($customerSubscription->start_date)->addDays($faker->numberBetween(0, 7));

            if ($scenario === 'full') {
                Payment::create([
                    'customer_id' => $customerSubscription->customer_id,
                    'customer_subscription_id' => $customerSubscription->id,
                    'amount' => $finalPrice,
                    'method' => $faker->randomElement(PaymentMethod::cases())->value,
                    'status' => 'paid',
                    'description' => 'Full payment',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            if ($scenario === 'split') {
                $half = round($finalPrice / 2, 2);

                Payment::create([
                    'customer_id' => $customerSubscription->customer_id,
                    'customer_subscription_id' => $customerSubscription->id,
                    'amount' => $half,
                    'method' => $faker->randomElement(PaymentMethod::cases())->value,
                    'status' => 'partial',
                    'description' => 'Split payment #1',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                Payment::create([
                    'customer_id' => $customerSubscription->customer_id,
                    'customer_subscription_id' => $customerSubscription->id,
                    'amount' => $half,
                    'method' => $faker->randomElement(PaymentMethod::cases())->value,
                    'status' => 'partial',
                    'description' => 'Split payment #2',
                    'created_at' => (clone $createdAt)->addDays($faker->numberBetween(1, 5)),
                    'updated_at' => (clone $createdAt)->addDays($faker->numberBetween(1, 5)),
                ]);
            }

            if ($scenario === 'pending') {
                Payment::create([
                    'customer_id' => $customerSubscription->customer_id,
                    'customer_subscription_id' => $customerSubscription->id,
                    'amount' => $finalPrice,
                    'method' => $faker->randomElement(PaymentMethod::cases())->value,
                    'status' => 'pending',
                    'description' => 'Awaiting payment confirmation',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            if ($scenario === 'failed') {
                Payment::create([
                    'customer_id' => $customerSubscription->customer_id,
                    'customer_subscription_id' => $customerSubscription->id,
                    'amount' => $finalPrice,
                    'method' => $faker->randomElement(PaymentMethod::cases())->value,
                    'status' => 'failed',
                    'description' => 'Failed payment',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $customerSubscription->recalculatePaymentSummary();
        }

        $expenseCategories = collect([
            ['Operations', false],
            ['Marketing', false],
            ['Maintenance', false],
            ['Payroll', true],
        ])->map(function (array $item) {
            [$name, $requiresStaff] = $item;

            return ExpenseCategory::firstOrCreate(
                ['name' => $name],
                ['requires_staff' => $requiresStaff],
            );
        });

        foreach (range(1, 120) as $i) {
            $category = $expenseCategories->random();
            $staffId = $category->requires_staff ? $staff->random()->id : null;

            Expense::create([
                'category_id' => $category->id,
                'amount' => $faker->randomFloat(2, 50000, 2500000),
                'expenses_date' => Carbon::now()->subDays($faker->numberBetween(0, 180))->toDateString(),
                'description' => $faker->sentence(8),
                'staff_id' => $staffId,
            ]);
        }

        $schedules = collect(range(1, 18))->map(function () use ($faker, $activities, $staff, $halls, $weekDays) {
            $hour = $faker->randomElement([7, 8, 9, 10, 16, 17, 18, 19]);
            $start = sprintf('%02d:00:00', $hour);
            $end = sprintf('%02d:00:00', $hour + 1);

            return Schedule::create([
                'activity_id' => $activities->random()->id,
                'trainer_id' => $staff->random()->id,
                'hall_id' => $halls->random()->id,
                'days_of_week' => collect($weekDays)->shuffle()->take($faker->numberBetween(2, 4))->values()->all(),
                'start_time' => $start,
                'end_time' => $end,
                'max_participants' => $faker->numberBetween(8, 20),
            ]);
        });

        foreach ($schedules as $schedule) {
            foreach (range(-14, 14) as $offset) {
                $date = Carbon::now()->addDays($offset);
                $dayKey = strtolower($date->format('l'));

                if (! in_array($dayKey, $schedule->days_of_week, true)) {
                    continue;
                }

                $occurrence = ScheduleOccurrence::create([
                    'schedule_id' => $schedule->id,
                    'date' => $date->toDateString(),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'max_participants' => $schedule->max_participants,
                    'status' => $date->isFuture() ? 'planned' : 'completed',
                ]);

                $visitors = $customers->shuffle()->take(rand(1, max(1, (int) floor($schedule->max_participants / 2))));
                foreach ($visitors as $customer) {
                    Visit::create([
                        'customer_id' => $customer->id,
                        'schedule_id' => $schedule->id,
                        'occurrence_id' => $occurrence->id,
                        'trainer_id' => $schedule->trainer_id,
                        'visited_at' => $date->copy()->setTimeFromTimeString((string) $schedule->start_time),
                        'status' => $faker->randomElement(['visited', 'visited', 'visited', 'missed', 'cancelled']),
                    ]);
                }
            }
        }
    }
}

