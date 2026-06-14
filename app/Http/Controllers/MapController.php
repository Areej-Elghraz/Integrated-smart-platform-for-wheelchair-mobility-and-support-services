<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Map\StoreMapRequest;
use App\Models\Floor;
use App\Models\Map;
use Illuminate\Support\Facades\Storage;

class MapController extends ApiController
{
    use \App\Traits\LogsAdminActions;

    /**
     * Store or update map associated with a floor.
     */
    public function store(Floor $floor, StoreMapRequest $request)
    {
        $this->authorize('update', $floor);

        // If a map already exists, we will update it. But we should delete old files.
        $existingMap = $floor->map;
        if ($existingMap) {
            $rawMapPath = $existingMap->getRawOriginal('map_file');
            if ($rawMapPath && Storage::disk('public')->exists($rawMapPath)) {
                Storage::disk('public')->delete($rawMapPath);
            }
            $rawYamlPath = $existingMap->getRawOriginal('yaml_file');
            if ($rawYamlPath && Storage::disk('public')->exists($rawYamlPath)) {
                Storage::disk('public')->delete($rawYamlPath);
            }
        }

        // Save map file
        $file = $request->file('map_file');
        $path = $file->store('maps', 'public');
        $extension = $file->getClientOriginalExtension() ?: 'pgm';

        // Save yaml file if exists
        $yamlFile = $request->file('yaml_file');
        $yamlPath = null;
        $yamlData = null;
        // $resolution = $request->input('resolution', 0.05);
        // $origin = $request->input('origin');
        // $mode = null;
        // $negate = null;
        // $occupied_thresh = null;
        // $free_thresh = null;

        if ($yamlFile) {
            $yamlPath = $yamlFile->store('maps', 'public');
            try {
                $yamlData = \Symfony\Component\Yaml\Yaml::parseFile($yamlFile->getRealPath());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error parsing YAML map file: ' . $e->getMessage());
            }
        }

        // Merge inputs into yamlData if they are provided in the request
        $rosFields = ['resolution', 'origin', 'mode', 'negate', 'occupied_thresh', 'free_thresh'];
        foreach ($rosFields as $field) {
            if ($request->has($field)) {
                $yamlData[$field] = $request->input($field);
            }
        }

        $width = $request->input('width');
        $height = $request->input('height');

        if (!$width || !$height) {
            // Attempt to read dimensions
            if (in_array(strtolower($extension), ['png', 'jpg', 'jpeg'])) {
                $sizes = @getimagesize($file->getRealPath());
                if ($sizes) {
                    $width = $sizes[0];
                    $height = $sizes[1];
                }
            } elseif (strtolower($extension) === 'pgm') {
                // Read PGM header to find dimensions
                $handle = fopen($file->getRealPath(), "r");
                $line1 = trim(fgets($handle)); // Magic number (P2, P5)
                // Skip comments
                do {
                    $line2 = trim(fgets($handle));
                } while (str_starts_with($line2, '#'));
                $dimensions = explode(' ', preg_replace('/\s+/', ' ', $line2));
                if (count($dimensions) >= 2) {
                    $width = $dimensions[0];
                    $height = $dimensions[1];
                }
                fclose($handle);
            }
        }

        $mapData = [
            'map_file'        => $path,
            'yaml_file'       => $yamlPath,
            'yaml_data'       => $yamlData,
            'extension'       => $extension,
            'width'           => $width ?: 0,
            'height'          => $height ?: 0,
        ];

        if ($existingMap) {
            $existingMap->update($mapData);
            $map = $existingMap->fresh();
            $this->logAdminAction('updated', $map);
        } else {
            $map = $floor->map()->create($mapData);
            $this->logAdminAction('created', $map);
        }

        broadcast(new \App\Events\MapUploaded($map))->toOthers();

        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.map.singular')]),
            status: 201,
            parameters: $map->toArray()
        );
    }

    /**
     * Display the map associated with the floor.
     */
    public function show(Floor $floor)
    {
        $this->authorize('view', $floor);

        $map = $floor->map;
        if (!$map) {
            return $this->errorResponse(
                message: __('messages.404_not_found', ['model' => __('messages.resources.map.singular')]),
                status: 404
            );
        }

        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.map.singular')]),
            status: 200,
            parameters: $map->loadMissing(['floor.places'])->toArray()
        );
    }

    /**
     * Delete the map associated with the floor.
     */
    public function destroy(Floor $floor)
    {
        $this->authorize('update', $floor);

        $map = $floor->map;
        if (!$map) {
            return $this->errorResponse(
                message: __('messages.404_not_found', ['model' => __('messages.resources.map.singular')]),
                status: 404
            );
        }

        $rawPath = $map->getRawOriginal('map_file');
        if ($rawPath && Storage::disk('public')->exists($rawPath)) {
            Storage::disk('public')->delete($rawPath);
        }

        $this->logAdminAction('deleted', $map);

        $map->delete();

        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.map.singular')]),
            status: 200
        );
    }
}
