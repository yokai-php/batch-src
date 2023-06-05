These are notes for processes that still need automation.

## Release

- Go on the sources package and draft a new release: https://github.com/yokai-php/batch-src/releases/new
- Create a new tag according to the changes you want to release: `0.x.y`
- Copy the tag name as the release title: `0.x.y`
- Use the `Generate release notes` button
- Switch to your local repository
- Fetch latest changes made to the sources repository:
```shell
git checkout 0.x
git fetch
git pull
```
- Synchronise source repository development branch with packages:
```shell
./scripts/split-branch 0.x
```
- Synchronise source repository created tag with packages:
```shell
./scripts/split-tag {created tag}
```
- Prepare the packages release note:
```markdown
## What's Changed

see https://github.com/yokai-php/batch-src/releases/tag/{created tag}

**Full Changelog**: https://github.com/yokai-php/{package name}/compare/{prev tag}...{created tag}
```
- Create a release for the tag, in each packages
  - https://github.com/yokai-php/batch/releases/new
  - https://github.com/yokai-php/batch-box-spout/releases/new
  - https://github.com/yokai-php/batch-doctrine-dbal/releases/new
  - https://github.com/yokai-php/batch-doctrine-orm/releases/new
  - https://github.com/yokai-php/batch-doctrine-persistence/releases/new
  - https://github.com/yokai-php/batch-league-flysystem/releases/new
  - https://github.com/yokai-php/batch-symfony-console/releases/new
  - https://github.com/yokai-php/batch-symfony-framework/releases/new
  - https://github.com/yokai-php/batch-symfony-messenger/releases/new
  - https://github.com/yokai-php/batch-symfony-serializer/releases/new
  - https://github.com/yokai-php/batch-symfony-validator/releases/new
  - https://github.com/yokai-php/batch-symfony-pack/releases/new
