<?php
 namespace IHORCHYSHKALA\IdeHelper\Classes;

 use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
 use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
 use Illuminate\Database\Eloquent\Model;
 use October\Rain\Database\Collection;
 use October\Rain\Database\Model as OctoberModel;

 class OctoberRelationsHook implements ModelHookInterface
 {
     /** Map of "relation type → builder class" */
     private const BUILDER_MAP = [
         // One-to-one
         'hasOne'             => \October\Rain\Database\Relations\HasOne::class,
         'hasOneThrough'      => \October\Rain\Database\Relations\HasOneThrough::class,
         'hasOneOrMany'       => \October\Rain\Database\Relations\HasOneOrMany::class,
         'morphOne'           => \October\Rain\Database\Relations\MorphOne::class,
         'morphOneOrMany'     => \October\Rain\Database\Relations\MorphOneOrMany::class,
         'attachOne'          => \October\Rain\Database\Relations\AttachOne::class,

         // One-to-many / Many-to-many
         'hasMany'            => \October\Rain\Database\Relations\HasMany::class,
         'hasManyThrough'     => \October\Rain\Database\Relations\HasManyThrough::class,
         'belongsToMany'      => \October\Rain\Database\Relations\BelongsToMany::class,
         'morphMany'          => \October\Rain\Database\Relations\MorphMany::class,
         'morphToMany'        => \October\Rain\Database\Relations\MorphToMany::class,
         'attachMany'         => \October\Rain\Database\Relations\AttachMany::class,
         'attachOneOrMany'    => \October\Rain\Database\Relations\AttachOneOrMany::class,
         'deferOneOrMany'     => \October\Rain\Database\Relations\DeferOneOrMany::class,

         // Inverse relations
         'belongsTo'          => \October\Rain\Database\Relations\BelongsTo::class,
         'morphTo'            => \October\Rain\Database\Relations\MorphTo::class,

         // Helper relations
         'definedConstraints' => \October\Rain\Database\Relations\DefinedConstraints::class,
     ];

     public function run(ModelsCommand $command, Model $model): void
     {
         if (!$model instanceof OctoberModel || !method_exists($model, 'getRelationDefinitions')) {
             return;
         }

         foreach ($model->getRelationDefinitions() as $relationType => $relations) {
             foreach ($relations as $name => $definition) {
                 $related  = $this->extractRelatedClass($definition);
                 $propType = $this->getPropertyType($relationType, $related);

                 // @property / @property-read
                 $command->setProperty($name, $propType, true, false);

                 // @method (if we know the relation class)
                 if (isset(self::BUILDER_MAP[$relationType]) && class_exists(self::BUILDER_MAP[$relationType])) {
                     $command->setMethod(
                         $name,
                         '\\' . self::BUILDER_MAP[$relationType],
                         []                              // without args = `linked_rooms()`
                     );
                 }
             }
         }
     }

     /** PHPDoc type for the relation property */
     private function getPropertyType(string $relationType, string $related): string
     {
         return match ($relationType) {
             // Single relations
             'hasOne', 'hasOneThrough', 'hasOneOrMany',
             'morphOne', 'morphOneOrMany',
             'belongsTo', 'morphTo',
             'attachOne'                     => "$related|null",

             // File attachments
             'attachMany', 'attachOneOrMany', 'deferOneOrMany'
             => '\\' . Collection::class . "|{$related}[]",

             // Other collection relations (hasMany, belongsToMany, morphMany...)
             default                          => '\\' . Collection::class . "|{$related}[]",
         };
     }

     /** Extract FQN of related model from definition */
     private function extractRelatedClass(string|array $def): string
     {
         return ltrim(is_string($def) ? $def : ($def[0] ?? $def['model'] ?? 'mixed'), '\\');
     }
 }