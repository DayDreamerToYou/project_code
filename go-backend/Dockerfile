# 多阶段构建：第一阶段编译，第二阶段运行
FROM golang:1.21-alpine AS builder

WORKDIR /app

# 复制 go.mod 和 go.sum
COPY go.mod go.sum* ./

# 下载依赖
RUN go mod download

# 复制源代码
COPY . .

# 编译（开启 CGO 关闭以避免 glibc 依赖）
ENV CGO_ENABLED=0 GOOS=linux GOARCH=amd64
RUN go build -ldflags="-s -w" -o /app/bin/server ./cmd/server

# === 第二阶段：运行镜像 ===
FROM alpine:3.20

# 时区与基础工具
RUN apk add --no-cache tzdata ca-certificates curl \
    && cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone

WORKDIR /app

# 复制编译产物
COPY --from=builder /app/bin/server /app/server

# 复制 .env 模板
COPY .env.example /app/.env.example

# 暴露端口
EXPOSE 8080

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# 启动
ENTRYPOINT ["/app/server"]
