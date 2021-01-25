app_name=files_automatedtagging

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version+=1.2.2

.PHONY: all
all: appstore

.PHONY: release
release: appstore create-tag

.PHONY: create-tag
create-tag:
	git tag -s -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

.PHONY: clean
clean:
	rm -rf $(build_dir)
	rm -rf node_modules
	rm -rf js/*
	rm -rf vendor/

.PHONY: build
build:
	mkdir -p js/
	npm ci
	npm run build
	#composer install --no-dev  #commented, because there are no no-dev deps

.PHONY: appstore
appstore: clean build
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/.babelrc.js \
	--exclude=/build \
	--exclude=/composer.* \
	--exclude=/docs \
	--exclude=/.eslintrc.js \
	--exclude=/package-lock.json \
	--exclude=/package.json \
	--exclude=/.php_cs.* \
	--exclude=/node_modules \
	--exclude=/translationfiles \
	--exclude=/.tx \
	--exclude=/tests \
	--exclude=/src \
	--exclude=/.stylelintrc.js \
	--exclude=/webpack.common.js \
	--exclude=/webpack.dev.js \
	--exclude=/webpack.prod.js \
	--exclude=/.drone.yml \
	--exclude=/.git \
	--exclude=/.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/.gitattributes \
	--exclude=/.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/Makefile \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name)-$(version).tar.gz | openssl base64; \
	fi
