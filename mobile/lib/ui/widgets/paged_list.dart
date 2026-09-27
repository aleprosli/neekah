import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/common.dart';
import '../../core/refresh_bus.dart';
import 'common.dart';
import 'feedback.dart';
import 'remote_view.dart';
import 'skeleton.dart';

/// Pages through a list endpoint: first page, more on scroll, refresh.
class PagedController<T> extends ChangeNotifier {
  PagedController(this._fetch);

  Future<Paginated<T>> Function(int page) _fetch;

  List<T> items = [];
  PageMeta? meta;
  Map<String, int> counts = const {};
  Object? error;
  bool loading = false;
  bool loadingMore = false;
  int _generation = 0;
  bool _disposed = false;

  bool get hasMore => meta?.hasMore ?? false;
  bool get isFirstLoad => loading && meta == null;

  /// Swaps the query (filter or search) and starts over.
  void updateFetch(Future<Paginated<T>> Function(int page) fetch) {
    _fetch = fetch;
    meta = null;
    items = [];
    refresh();
  }

  Future<void> refresh() async {
    final generation = ++_generation;
    loading = true;
    error = null;
    _notify();
    try {
      final page = await _fetch(1);
      if (generation != _generation) {
        return;
      }
      items = List.of(page.items);
      meta = page.meta;
      counts = page.counts;
    } on Exception catch (e) {
      if (generation != _generation) {
        return;
      }
      error = e;
    }
    loading = false;
    _notify();
  }

  Future<void> loadMore() async {
    if (loading || loadingMore || !hasMore) {
      return;
    }
    final generation = _generation;
    loadingMore = true;
    _notify();
    try {
      final page = await _fetch(meta!.currentPage + 1);
      if (generation == _generation) {
        items = [...items, ...page.items];
        meta = page.meta;
        counts = page.counts.isEmpty ? counts : page.counts;
      }
    } on Exception {
      // Scrolling again retries.
    }
    loadingMore = false;
    _notify();
  }

  void replaceWhere(bool Function(T item) test, T Function(T item) update) {
    items = [for (final item in items) test(item) ? update(item) : item];
    _notify();
  }

  void _notify() {
    if (!_disposed) {
      notifyListeners();
    }
  }

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }
}

/// A pull-to-refresh, infinite-scroll list over a [PagedController].
class PagedListView<T> extends StatefulWidget {
  const PagedListView({
    required this.controller,
    required this.itemBuilder,
    required this.empty,
    this.header,
    this.topics = const {},
    this.padding = const EdgeInsets.fromLTRB(16, 8, 16, 32),
    this.separator = 12,
    super.key,
  });

  final PagedController<T> controller;
  final Widget Function(BuildContext context, T item) itemBuilder;
  final Widget empty;
  final Widget? header;
  final Set<Topic> topics;
  final EdgeInsets padding;
  final double separator;

  @override
  State<PagedListView<T>> createState() => _PagedListViewState<T>();
}

class _PagedListViewState<T> extends State<PagedListView<T>> {
  RefreshBus? _bus;
  Map<Topic, int> _seen = const {};

  @override
  void initState() {
    super.initState();
    widget.controller.addListener(_changed);
    if (widget.controller.meta == null && !widget.controller.loading) {
      widget.controller.refresh();
    }
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final bus = Provider.of<RefreshBus?>(context, listen: false);
    if (bus != _bus) {
      _bus?.removeListener(_onBus);
      _bus = bus;
      _seen = {for (final topic in widget.topics) topic: bus?.version(topic) ?? 0};
      bus?.addListener(_onBus);
    }
  }

  @override
  void didUpdateWidget(covariant PagedListView<T> oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.controller != widget.controller) {
      oldWidget.controller.removeListener(_changed);
      widget.controller.addListener(_changed);
    }
  }

  @override
  void dispose() {
    widget.controller.removeListener(_changed);
    _bus?.removeListener(_onBus);
    super.dispose();
  }

  void _changed() {
    if (mounted) {
      setState(() {});
    }
  }

  void _onBus() {
    final bus = _bus!;
    if (widget.topics.any((topic) => bus.version(topic) != _seen[topic])) {
      _seen = {for (final topic in widget.topics) topic: bus.version(topic)};
      widget.controller.refresh();
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = widget.controller;

    Widget body;
    if (controller.isFirstLoad) {
      body = const SkeletonList(key: ValueKey('skeleton'));
    } else if (controller.error != null && controller.items.isEmpty) {
      body = LayoutBuilder(
        key: const ValueKey('error'),
        builder: (context, constraints) => ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: ErrorState(error: controller.error!, onRetry: controller.refresh),
            ),
          ],
        ),
      );
    } else if (controller.items.isEmpty) {
      body = LayoutBuilder(
        key: const ValueKey('empty'),
        builder: (context, constraints) => ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight * 0.85),
              child: widget.empty,
            ),
          ],
        ),
      );
    } else {
      final count = controller.items.length + (controller.hasMore ? 1 : 0);
      body = NotificationListener<ScrollNotification>(
        key: const ValueKey('list'),
        onNotification: (notification) {
          if (notification.metrics.extentAfter < 400) {
            controller.loadMore();
          }

          return false;
        },
        child: ListView.separated(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: widget.padding,
          itemCount: count,
          separatorBuilder: (_, _) => SizedBox(height: widget.separator),
          itemBuilder: (context, index) {
            if (index >= controller.items.length) {
              return const Padding(
                padding: EdgeInsets.all(16),
                child: Center(child: SizedBox(width: 26, height: 26, child: CircularProgressIndicator(strokeWidth: 2.5))),
              );
            }

            return widget.itemBuilder(context, controller.items[index]);
          },
        ),
      );
    }

    return BrandRefresh(
      onRefresh: () async {
        await controller.refresh();
        if (context.mounted && controller.error != null && controller.items.isNotEmpty) {
          showError(context, controller.error!);
        }
      },
      child: AnimatedSwitcher(duration: const Duration(milliseconds: 240), child: body),
    );
  }
}
