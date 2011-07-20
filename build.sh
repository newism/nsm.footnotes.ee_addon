#!/bin/bash
path=releases/${2}
third_party_path=${path}/system/expressionengine/third_party/${1}
mkdir -p ${third_party_path}
cp config.php ${third_party_path}
cp pi.* ${third_party_path}
cp LICENSE.md ${third_party_path}
cp README.md ${third_party_path}
cp CHANGELOG.md ${third_party_path}
cp -R templates ${path}/system/expressionengine/