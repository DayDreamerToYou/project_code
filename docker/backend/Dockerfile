# ============================================
# Dockerfile - Laravel 后端服务镜像构建文件
# ============================================
# 功能说明：
# 1. 基于 PHP 8.4 FPM Alpine 镜像构建
# 2. 安装必要的系统依赖和 PHP 扩展
# 3. 配置 Composer 和 Laravel 应用
# 4. 设置健康检查和启动命令
# ============================================

# ============================================
# 第一部分：基础镜像
# ============================================

# FROM: 指定基础镜像
# php:8.4-fpm-alpine: PHP 8.4 FPM 版本，基于 Alpine Linux
# 
# 为什么选择这个镜像？
# - PHP 8.4: 最新稳定版本，性能更好，特性更多
# - FPM: FastCGI Process Manager，适合生产环境
# - Alpine: 轻量级 Linux 发行版，镜像小（~50MB），安全
# 
# 对比：
# - php:8.4-fpm: 基于 Debian，镜像大（~400MB）
# - php:8.4-fpm-alpine: 基于 Alpine，镜像小（~50MB）
FROM php:8.4-fpm-alpine

# ============================================
# 第二部分：元数据标签
# ============================================

# LABEL: 添加镜像元数据标签
# maintainer: 维护者信息
# description: 镜像描述
# 
# 作用：
# - 方便镜像管理和识别
# - 记录维护者信息
# - 描述镜像用途
LABEL maintainer="Fishery System"
LABEL description="Backend container for Fishery Data Management System"

# ============================================
# 第三部分：安装系统依赖
# ============================================

# RUN: 执行命令并提交结果（创建新的镜像层）
# apk: Alpine Linux 的包管理器（类似 apt、yum）
# add: 安装包
# --no-cache: 不使用缓存，减小镜像大小
# 
# 安装的包说明：
# - curl: HTTP 客户端工具，用于健康检查、API 调用
# - git: 版本控制工具，Composer 依赖
# - zip/unzip: 压缩解压工具，Composer 依赖
# - vim: 文本编辑器，方便调试
# - nginx: Web 服务器（虽然主要用 PHP 内置服务器，但保留备用）
# - mysql-client: MySQL 客户端，用于数据库连接测试
# - libzip-dev: ZIP 库开发文件，PHP zip 扩展依赖
# - libpng-dev: PNG 库开发文件，PHP gd 扩展依赖
# - libjpeg-turbo-dev: JPEG 库开发文件，PHP gd 扩展依赖
# - freetype-dev: FreeType 库开发文件，PHP gd 扩展依赖
# - libxml2-dev: XML 库开发文件，PHP xml 扩展依赖
# - oniguruma-dev: 正则表达式库，PHP mbstring 扩展依赖
# - tzdata: 时区数据，用于配置时区
# 
# 为什么需要这些包？
# - 开发工具：git, vim, curl
# - Composer 依赖：git, zip, unzip
# - PHP 扩展依赖：lib*-dev 包
# - 数据库工具：mysql-client
# - 时区支持：tzdata
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    vim \
    nginx \
    mysql-client \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    tzdata

# ============================================
# 第四部分：配置时区
# ============================================

# ENV: 设置环境变量
# TZ=Asia/Shanghai: 设置时区为中国上海时区（东八区）
# 
# 为什么需要设置时区？
# - 默认时区是 UTC（协调世界时）
# - 中国时区是 Asia/Shanghai（UTC+8）
# - 确保日志、数据库时间正确
# - 避免时区相关的 Bug
ENV TZ=Asia/Shanghai

# RUN: 执行时区配置命令
# ln -snf: 创建符号链接（软链接）
# /usr/share/zoneinfo/$TZ: 时区数据文件
# /etc/localtime: 系统时区配置文件
# echo $TZ > /etc/timezone: 写入时区名称
# 
# 命令解释：
# - ln -s: 创建软链接（-s）
# - -n: 如果目标已存在，不解引用
# - -f: 强制覆盖已存在的链接
# - /usr/share/zoneinfo/Asia/Shanghai: 时区数据源文件
# - /etc/localtime: 时区配置目标文件
# 
# 效果：
# - 系统时间显示为中国时间
# - PHP 时间函数返回正确时区
# - 日志时间戳正确
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# ============================================
# 第五部分：安装 PHP 扩展
# ============================================

# RUN: 配置和安装 PHP 扩展
# docker-php-ext-configure: 配置 PHP 扩展编译选项
# gd: GD 图像处理库
# --with-freetype: 启用 FreeType 支持（字体渲染）
# --with-jpeg: 启用 JPEG 支持（图片处理）
# 
# docker-php-ext-install: 安装 PHP 扩展
# -j$(nproc): 并行编译，使用所有 CPU 核心
# 
# 安装的 PHP 扩展说明：
# - pdo_mysql: PDO MySQL 驱动，Laravel 数据库连接
# - mysqli: MySQL 改进版扩展，数据库连接
# - zip: ZIP 压缩扩展，Composer 依赖
# - gd: GD 图像处理库，图片处理、验证码生成
# - opcache: PHP 字节码缓存，提升性能（重要！）
# - mbstring: 多字节字符串处理，中文支持
# - xml: XML 处理扩展，Laravel 依赖
# - bcmath: 任意精度数学计算，财务计算
# 
# 为什么需要这些扩展？
# - 数据库：pdo_mysql, mysqli
# - 性能优化：opcache（缓存字节码，提升 50-100% 性能）
# - 图片处理：gd（上传图片、生成缩略图）
# - 中文支持：mbstring（处理中文字符串）
# - Composer：zip
# - Laravel：xml, bcmath
# 
# opcache 的重要性：
# - 缓存编译后的 PHP 字节码
# - 避免每次请求都编译 PHP 代码
# - 提升性能 50-100%
# - 生产环境必备
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        opcache \
        mbstring \
        xml \
        bcmath

# ============================================
# 第六部分：安装 Composer
# ============================================

# COPY: 复制文件或目录
# --from=composer:latest: 从另一个镜像复制文件（多阶段构建）
# /usr/bin/composer: 源文件路径（composer 镜像中的路径）
# /usr/bin/composer: 目标文件路径（当前镜像中的路径）
# 
# 多阶段构建说明：
# - composer:latest: 官方 Composer 镜像
# - 从该镜像中复制 composer 可执行文件
# - 避免单独下载和安装 Composer
# - 减小镜像大小
# 
# Composer 是什么？
# - PHP 依赖管理工具（类似 npm、pip）
# - 管理项目依赖包
# - 自动加载类文件
# - Laravel 必需工具
# 
# 为什么从镜像复制？
# - 官方镜像已包含最新版本
# - 避免下载和验证
# - 更安全、更快速
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================
# 第七部分：设置工作目录
# ============================================

# WORKDIR: 设置工作目录
# /var/www/html: Laravel 项目目录
# 
# 作用：
# - 后续命令都在这个目录下执行
# - 容器启动后的默认目录
# - Laravel 项目标准路径
# 
# 为什么选择这个路径？
# - /var/www: Web 项目标准目录
# - html: Web 根目录
# - Laravel 项目默认路径
WORKDIR /var/www/html

# ============================================
# 第八部分：复制项目代码
# ============================================

# COPY: 复制文件或目录
# . .: 复制当前目录所有文件到工作目录
# 
# 第一个 .: 源路径（构建上下文，即 backend 目录）
# 第二个 .: 目标路径（工作目录 /var/www/html）
# 
# 复制的内容：
# - app/: Laravel 应用代码
# - bootstrap/: Laravel 启动文件
# - config/: 配置文件
# - database/: 数据库迁移和填充
# - public/: 公共文件（入口文件）
# - resources/: 资源文件（视图、语言）
# - routes/: 路由定义
# - storage/: 存储目录（日志、缓存）
# - tests/: 测试文件
# - composer.json: 依赖配置
# - artisan: Laravel 命令行工具
# 
# 注意：
# - .dockerignore 文件可以排除不需要的文件
# - 避免复制 vendor/ 目录（通过 composer install 生成）
# - 避免复制 .git/ 目录（减小镜像大小）
COPY . .

# ============================================
# 第九部分：安装 PHP 依赖
# ============================================

# RUN: 执行 Composer 安装依赖
# composer install: 安装 composer.json 中定义的依赖
# --no-dev: 不安装开发依赖（生产环境）
# --optimize-autoloader: 优化自动加载器（提升性能）
# --no-interaction: 非交互模式（不询问用户）
# 
# 参数说明：
# --no-dev:
#   - 不安装 require-dev 中的包
#   - 减小依赖体积
#   - 生产环境推荐
#   - 开发依赖：测试工具、调试工具
# 
# --optimize-autoloader:
#   - 优化自动加载器性能
#   - 生成类映射文件
#   - 提升类加载速度 20-30%
#   - 生产环境必备
# 
# --no-interaction:
#   - 不询问用户确认
#   - 适合自动化构建
#   - 避免构建卡住
# 
# 安装的依赖（来自 composer.json）：
# - laravel/framework: Laravel 框架
# - 其他业务依赖包
# 
# 生成的内容：
# - vendor/: 依赖包目录
# - vendor/autoload.php: 自动加载文件
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ============================================
# 第十部分：设置文件权限
# ============================================

# RUN: 设置文件和目录权限
# chown: 更改文件所有者和组
# -R: 递归处理（包括子目录和文件）
# www-data:www-data: 所有者和组（Nginx/PHP-FPM 默认用户）
# /var/www/html: 目标目录
# 
# chmod: 更改文件权限
# -R: 递归处理
# 755: 所有者可读写执行，其他人可读执行
# 775: 所有者和组可读写执行，其他人可读执行
# 
# 权限说明：
# 755 (rwxr-xr-x):
#   - 所有者: 读(4) + 写(2) + 执行(1) = 7
#   - 组: 读(4) + 执行(1) = 5
#   - 其他: 读(4) + 执行(1) = 5
# 
# 775 (rwxrwxr-x):
#   - 所有者: 读(4) + 写(2) + 执行(1) = 7
#   - 组: 读(4) + 写(2) + 执行(1) = 7
#   - 其他: 读(4) + 执行(1) = 5
# 
# 为什么需要设置权限？
# - PHP-FPM 以 www-data 用户运行
# - 需要读取项目文件
# - storage/ 和 bootstrap/cache/ 需要写入权限
#   - storage/: 日志、缓存、上传文件
#   - bootstrap/cache/: 启动缓存文件
# 
# 为什么 storage 和 bootstrap/cache 需要 775？
# - 需要写入权限
# - Laravel 需要创建和修改文件
# - 日志文件、缓存文件、编译文件
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# ============================================
# 第十一部分：复制 PHP 配置文件
# ============================================

# COPY: 复制 PHP 配置文件
# php.ini: 源文件（构建上下文中的 php.ini）
# /usr/local/etc/php/php.ini: 目标路径（PHP 配置文件路径）
# 
# php.ini 配置内容：
# - memory_limit: 内存限制
# - upload_max_filesize: 上传文件大小限制
# - post_max_size: POST 数据大小限制
# - opcache 配置: 字节码缓存设置
# - 其他性能和安全配置
# 
# 为什么需要自定义配置？
# - 默认配置不适合生产环境
# - 需要调整内存和上传限制
# - 优化 OPcache 性能
# - 禁用危险函数
COPY php.ini /usr/local/etc/php/php.ini

# ============================================
# 第十二部分：健康检查
# ============================================

# HEALTHCHECK: 定义健康检查命令
# --interval=30s: 每 30 秒检查一次
# --timeout=3s: 检查命令超时时间 3 秒
# --start-period=10s: 容器启动后 10 秒开始检查
# --retries=3: 连续失败 3 次才标记为不健康
# 
# CMD: 健康检查命令
# curl -f: 失败时返回非零退出码
# http://localhost:8001/api/health: 健康检查端点
# || exit 1: 如果 curl 失败，返回退出码 1（不健康）
# 
# 健康检查流程：
# 1. 容器启动后等待 10 秒（start-period）
# 2. 每 30 秒执行一次 curl 命令（interval）
# 3. 如果 3 秒内未响应，标记为超时（timeout）
# 4. 连续失败 3 次，标记为不健康（retries）
# 5. 成功响应，标记为健康
# 
# 为什么需要健康检查？
# - Docker 可以监控容器健康状态
# - 自动重启不健康的容器
# - 负载均衡器可以剔除不健康的容器
# - Kubernetes/Docker Swarm 编排依赖健康状态
# 
# /api/health 端点：
# - Laravel 路由定义
# - 返回 {"status": "ok"}
# - 验证应用和数据库连接正常
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8001/api/health || exit 1

# ============================================
# 第十三部分：暴露端口
# ============================================

# EXPOSE: 声明容器监听的端口
# 8001: Laravel 应用端口
# 
# 作用：
# - 文档化端口用途
# - 方便端口映射
# - Docker Compose 可以自动链接
# 
# 注意：
# - EXPOSE 不会真正发布端口
# - 只是声明容器监听的端口
# - 需要通过 -p 或 -P 参数映射端口
# 
# 为什么选择 8001？
# - 避免与常见端口冲突（80, 8080, 3000）
# - 前端使用 8080
# - 后端使用 8001
# - 容易区分前后端服务
EXPOSE 8001

# ============================================
# 第十四部分：设置工作目录（再次确认）
# ============================================

# WORKDIR: 再次设置工作目录
# /var/www/html: Laravel 项目目录
# 
# 为什么再次设置？
# - 确保容器启动时在正确目录
# - 避免路径问题
# - 确保 artisan 命令正确执行
WORKDIR /var/www/html

# ============================================
# 第十五部分：启动命令
# ============================================

# CMD: 容器启动时执行的命令
# php artisan serve: 启动 Laravel 内置服务器
# --host=0.0.0.0: 监听所有网络接口（允许外部访问）
# --port=8001: 监听 8001 端口
# 
# artisan serve 说明：
# - Laravel 内置的开发服务器
# - 基于 PHP 内置 Web 服务器
# - 适合开发和测试环境
# 
# 为什么监听 0.0.0.0？
# - 默认只监听 localhost（127.0.0.1）
# - 容器内需要监听所有接口
# - 才能从容器外部访问
# - 0.0.0.0 表示所有网络接口
# 
# 生产环境建议：
# - 使用 PHP-FPM + Nginx
# - 性能更好、更稳定
# - 支持更多并发
# - 但开发环境 artisan serve 足够用
# 
# CMD vs ENTRYPOINT:
# - CMD: 可以被 docker run 参数覆盖
# - ENTRYPOINT: 不容易被覆盖
# - 这里使用 CMD，方便调试时覆盖命令
CMD php artisan serve --host=0.0.0.0 --port=8001

# ============================================
# 构建流程总结
# ============================================
# 
# 1. 基础镜像: php:8.4-fpm-alpine (~50MB)
# 2. 安装系统依赖: curl, git, mysql-client 等
# 3. 配置时区: Asia/Shanghai
# 4. 安装 PHP 扩展: pdo_mysql, opcache, gd 等
# 5. 安装 Composer: 依赖管理工具
# 6. 复制项目代码: Laravel 应用
# 7. 安装 PHP 依赖: composer install
# 8. 设置文件权限: www-data 用户
# 9. 复制 PHP 配置: php.ini
# 10. 配置健康检查: /api/health
# 11. 暴露端口: 8001
# 12. 启动服务: php artisan serve
# 
# 最终镜像大小: ~273MB
# 包含: PHP 8.4, Laravel, 所有依赖, 配置文件
# ============================================
