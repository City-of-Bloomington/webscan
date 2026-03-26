SHELL := /bin/bash
APPNAME := webscan

REQS := sassc msgfmt
K := $(foreach r, ${REQS}, $(if $(shell command -v ${r} 2> /dev/null), '', $(error "${r} not installed")))

VERSION := $(shell cat VERSION | tr -d "[:space:]")
COMMIT := $(shell git rev-parse --short HEAD)

default: test clean compile package

clean:
	rm -Rf build/${APPNAME}*

compile:

package:
	[[ -d build ]] || mkdir build
	rsync -rl --exclude-from=buildignore . build/${APPNAME}
	cd build && tar czf ${APPNAME}-${VERSION}-${COMMIT}.tar.gz ${APPNAME}

test:
	vendor/bin/phpunit -c src/Test/phpunit.xml --testsuite Unit
	vendor/bin/phpstan analyse -l 0
