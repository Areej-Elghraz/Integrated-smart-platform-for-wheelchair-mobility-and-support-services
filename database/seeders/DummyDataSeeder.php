<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Place;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Review;
use App\Models\Favorite;
use App\Models\Visitor;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SupportMessage;
use App\Models\Device;
use App\Models\MedicalCondition;
use App\Models\Wheelchair;

use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $medicalConds = \App\Models\MedicalCondition::all();

        // 1. Users
        $users = User::factory(10)->create();
        foreach ($users as $user) {
            if ($user->role === 'user') {
                // Attach 1-2 random medical conditions
                if ($medicalConds->count() > 0) {
                    $mcToAttach = $medicalConds->random(rand(1, 2))->pluck('id')->toArray();
                    $user->medicalConditions()->sync($mcToAttach);
                }
            }
        }

        // Seed some wheelchairs
        $wheelchairUsers = $users->where('role', 'user')->take(5)->values();
        foreach (range(0, 4) as $idx) {
            if (!isset($wheelchairUsers[$idx])) continue;
            $wheelchair = Wheelchair::create([
                'serial_number' => 'WC-' . Str::upper(Str::random(8)),
                'battery' => rand(10, 100),
                'voltage' => 24.5,
                'current' => 2.1,
                'temperature' => 35.5,
                'connection_state' => collect(['online', 'offline'])->random(),
                'user_id' => $wheelchairUsers[$idx]->id,
            ]);
        }

        // 2. Countries and Cities
        $countries = Country::factory(3)->create();
        $cities = collect();
        foreach ($countries as $country) {
            $cities = $cities->merge(City::factory(2)->create(['country_id' => $country->id]));
        }

        // 3. Categories
        $categories = Category::factory(5)->create(['owner_id' => $users->random()->id]);

        // 4. Organizations
        $organizations = collect();
        foreach (range(1, 5) as $i) {
            $org = Organization::factory()->create([
                'owner_id' => $users->random()->id,
                'country_id' => $countries->random()->id,
                'city_id' => $cities->random()->id,
            ]);
            // Pivot category (if relationship is configured correctly)
            try {
                $org->categories()->attach($categories->random()->id);
            } catch (\Exception $e) {}
            $organizations->push($org);
        }

        // 5. Places
        $places = collect();
        foreach (range(1, 10) as $i) {
            $place = Place::factory()->create([
                'owner_id' => $users->random()->id,
                'organization_id' => $organizations->random()->id,
            ]);
            // Pivot category
            try {
                $place->categories()->attach($categories->random()->id);
            } catch (\Exception $e) {}
            $places->push($place);
        }

        // 6. Posts
        $posts = collect();
        foreach (range(1, 10) as $i) {
            $post = Post::create([
                'user_id' => $users->random()->id,
                'content' => 'This is a dummy post ' . Str::random(5),
                'images' => ['https://via.placeholder.com/150'],
            ]);
            $posts->push($post);
        }

        // 7. Comments
        $comments = collect();
        foreach ($posts as $post) {
            $comment = Comment::create([
                'user_id' => $users->random()->id,
                'commentable_id' => $post->id,
                'commentable_type' => get_class($post),
                'content' => 'Nice post! ' . Str::random(3),
            ]);
            $comments->push($comment);
            
            // Nested Comment
            Comment::create([
                'user_id' => $users->random()->id,
                'commentable_id' => $post->id,
                'commentable_type' => get_class($post),
                'content' => 'I agree!',
                'parent_id' => $comment->id,
            ]);
        }

        // 8. Likes
        foreach ($posts as $post) {
            Like::create([
                'user_id' => $users->random()->id,
                'likeable_id' => $post->id,
                'likeable_type' => get_class($post),
            ]);
        }
        foreach ($comments as $comment) {
            Like::create([
                'user_id' => $users->random()->id,
                'likeable_id' => $comment->id,
                'likeable_type' => get_class($comment),
            ]);
        }

        // 9. Reviews
        foreach ($places as $place) {
            Review::create([
                'user_id' => $users->random()->id,
                'reviewable_id' => $place->id,
                'reviewable_type' => get_class($place),
                'rating' => rand(1, 5),
                'comment' => 'Great place!',
            ]);
        }

        foreach ($organizations as $org) {
            Review::create([
                'user_id' => $users->random()->id,
                'reviewable_id' => $org->id,
                'reviewable_type' => get_class($org),
                'rating' => rand(1, 5),
                'comment' => 'Good organization.',
            ]);
        }

        // 10. Favorites
        foreach ($places->take(5) as $place) {
            Favorite::create([
                'user_id' => $users->random()->id,
                'favoritable_id' => $place->id,
                'favoritable_type' => get_class($place),
            ]);
        }

        // 11. Visitors
        foreach ($places->take(5) as $place) {
            Visitor::create([
                'user_id' => $users->random()->id,
                'visitable_id' => $place->id,
                'visitable_type' => get_class($place),
            ]);
        }

        // 12. Conversations & Messages
        for ($i = 0; $i < 3; $i++) {
            $user1 = $users->random();
            $user2 = $users->except([$user1->id])->random();

            $conversation = Conversation::create([
                'user_one_id' => $user1->id,
                'user_two_id' => $user2->id,
                'deleted_by_user_one' => false,
                'deleted_by_user_two' => false,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user1->id,
                'type' => 'text',
                'content' => 'Hello there!',
                'is_read' => true,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user2->id,
                'type' => 'text',
                'content' => 'Hi! How are you?',
                'is_read' => false,
            ]);
        }

        // 13. Support Messages
        for ($i = 0; $i < 3; $i++) {
            $user = $users->random();
            SupportMessage::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '+1234567890',
                'message' => 'I need some help with my account.',
            ]);
        }
        
        // 14. Devices
        try {
           Device::create([
                'name' => 'iPhone 13',
                'type' => 'ios',
                'ip' => '127.0.0.1',
            ]);
        } catch (\Exception $e) {
            // Options error ignored
        }
    }
}
