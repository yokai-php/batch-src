matrix=(
  "php74/4.4.*"
  "php80/5.3.*"
  "php81/5.4.x-dev@dev"
  "php81/6.0.x-dev@dev"
)
for entry in "${matrix[@]}"
do
  IFS='/' read -ra config <<< "${entry}"
  container=${config[0]}
  symfony=${config[1]}
  echo "${entry}"
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/console:${symfony}
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/filesystem:${symfony} --dev
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/framework-bundle:${symfony}
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/messenger:${symfony}
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/process:${symfony}
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/serializer:${symfony}
  led in -s ${container} -- composer2 --no-interaction --quiet require --no-update symfony/validator:${symfony}
  led in -s ${container} -- composer2 --no-interaction update --no-progress --with-all-dependencies
  led in -s ${container} -- vendor/bin/phpunit
done

echo "Revert changes made to composer.json"
git restore composer.json
