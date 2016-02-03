<?php

namespace SourceBroker\InstanceManager\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BaseConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deploy');

        $rootNode
            ->children()
                ->scalarNode('secret')->isRequired()->end()
                ->arrayNode('defaults')
                    ->children()
                        ->arrayNode('type')
                            ->children()
                                ->arrayNode('_allTypes')
                                    ->children()
                                        ->arrayNode('proxy')
                                            ->children()
                                                ->scalarNode('user')->end()
                                                ->scalarNode('host')->end()
                                                ->scalarNode('port')->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('instance')
                                            ->children()
                                                ->arrayNode('accessIp')
                                                    ->prototype('scalar')->end()
                                                ->end()
                                             ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('media')
                                    ->children()
                                        ->arrayNode('options')
                                            ->children()
                                                ->arrayNode('folders')
                                                    ->children()
                                                        ->append($this->addFolders('typo3', $required = false))
                                                        ->append($this->addFolders('magento', $required = false))
                                                    ->end()
                                                ->end()
                                                ->arrayNode('excludePatterns')
                                                    ->children()
                                                        ->append($this->addFoldersExcludePatterns('typo3'))
                                                        ->append($this->addFoldersExcludePatterns('magento'))
                                                        ->append($this->addFoldersExcludePatterns('multimedia'))
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('database')
                                    ->children()
                                        ->arrayNode('options')
                                            ->children()
                                                ->arrayNode('ignoreTables')
                                                    ->children()
                                                        ->append($this->addIgnoreTables('typo3'))
                                                        ->append($this->addIgnoreTables('magento'))
                                                    ->end()
                                                ->end()
                                                ->arrayNode('postImportSql')
                                                    ->children()
                                                        ->arrayNode('typo3')
                                                           ->prototype('scalar')->end()
                                                        ->end()
                                                        ->arrayNode('magento')
                                                            ->prototype('scalar')->end()
                                                        ->end()
                                                        ->arrayNode('symfony')
                                                            ->prototype('scalar')->end()
                                                        ->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('type')->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->appendTypeAllConfiguration())
                        ->append($this->appendTypeConfiguration('deploy'))
                        ->append($this->appendTypeConfiguration('access'))
                        ->append($this->appendTypeConfiguration('media', true))
                        ->append($this->appendTypeConfiguration('database', false))
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    protected function appendTypeConfiguration($name, $req = false)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        $node
            ->prototype('array')
                ->children()
                    ->scalarNode('active')->defaultFalse()->end()
                    ->arrayNode('proxy')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('user')->end()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('basePath')->defaultValue('/home/%s/')->end()
                            ->scalarNode('deployPath')->defaultValue('/home/%s/deploy/')->end()
                            ->scalarNode('downloadPath')->defaultValue('/home/%s/synchro/')->end()
                            ->scalarNode('synchroPath')->defaultValue('/home/%s/synchro/')->end()
                            ->scalarNode('privKeyFile')->end()
                            ->booleanNode('doNotCallPusher')->defaultFalse()->end()
                        ->end()
                    ->end()
                    ->arrayNode('instance')
                        ->children()
                            ->scalarNode('host')->isRequired()->end()
                            ->scalarNode('port')->end()
                            ->arrayNode('accessIp')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('options')
                        ->children()
                            ->append($this->addFolders('folders', $req))
                            ->append($this->addFoldersExcludePatterns('excludePatterns'))
                            ->append($this->addIgnoreTables('ignoreTables'))
                            ->append($this->addDatabases($req))
                            ->scalarNode('postImportSql')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    protected function appendTypeAllConfiguration()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_allTypes');

        $node
            ->prototype('array')
                ->children()
                    ->scalarNode('active')->defaultFalse()->end()
                    ->arrayNode('proxy')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('user')->end()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('basePath')->defaultValue('/home/%s/')->end()
                            ->scalarNode('deployPath')->defaultValue('/home/%s/deploy/')->end()
                            ->scalarNode('downloadPath')->defaultValue('/home/%s/synchro/')->end()
                            ->scalarNode('synchroPath')->defaultValue('/home/%s/synchro/')->end()
                            ->scalarNode('privKeyFile')->end()
                            ->booleanNode('doNotCallPusher')->defaultFalse()->end()
                        ->end()
                    ->end()
                    ->arrayNode('instance')
                        ->children()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->arrayNode('accessIp')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('options')
                        ->children()
                            ->append($this->addFolders('folders', $required = true))
                            ->append($this->addFoldersExcludePatterns('excludePatterns'))
                            ->append($this->addIgnoreTables('ignoreTables'))
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }

    protected function addFoldersExcludePatterns($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        $node->prototype('array')
            ->children()
                ->scalarNode('pattern')->isRequired()->end()
                ->scalarNode('mode')->end()
            ->end()
        ->end();
        return $node;
    }

    protected function addFolders($name, $required = true)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        $nodePart = $node->prototype('array')
        ->children()
        ->scalarNode('folder');

        if($required) {
            $nodePart = $nodePart->isRequired();
        }

        $nodePart->end()
            ->scalarNode('maxFileSize')->end()
            ->scalarNode('mode')->end()
            ->end()
            ->end();
        return $node;
    }

    protected function addDatabases($required)
    {
        $builder = new TreeBuilder();
        $node = $builder->root('databases');

        $nodePart = $node->prototype('array')
            ->children()
            ->scalarNode('code');

        if($required) {
            $nodePart = $nodePart->isRequired();
        }

        $nodePart->end()
            ->scalarNode('dbname')->end()
            ->scalarNode('user')->end()
            ->scalarNode('password')->end()
            ->scalarNode('host')->end()
            ->scalarNode('port')->end()
            ->scalarNode('configFile')->end()
            ->scalarNode('postImportSql')->end()
            ->append($this->addIgnoreTables('ignoreTables'))
            ->scalarNode('driver')->end()
            ->scalarNode('charset')->end()
            ->end()
            ->end();
        return $node;
    }

    protected function addIgnoreTables($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        $node->prototype('scalar')->end();

        return $node;
    }


}