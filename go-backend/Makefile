.PHONY: run build test fmt vet tidy clean

# 默认目标
.DEFAULT_GOAL := help

help:
	@echo "可用命令:"
	@echo "  make run        - 本地运行"
	@echo "  make build      - 编译二进制"
	@echo "  make test       - 运行测试"
	@echo "  make fmt        - 格式化代码"
	@echo "  make vet        - 静态检查"
	@echo "  make tidy       - 整理 go.mod"
	@echo "  make dev        - 启动 air 热重载（需先安装 air）"
	@echo "  make docker     - 构建 Docker 镜像"

run:
	go run ./cmd/server

build:
	go build -o bin/server ./cmd/server

test:
	go test -v ./...

fmt:
	go fmt ./...

vet:
	go vet ./...

tidy:
	go mod tidy

dev:
	@command -v air >/dev/null 2>&1 || go install github.com/air-verse/air@latest
	air

docker:
	docker build -t table-editor-backend:latest .

clean:
	rm -rf bin/ tmp/
