import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/refresh_bus.dart';
import '../theme.dart';
import 'common.dart';
import 'feedback.dart';
import 'skeleton.dart';

typedef RemoteBuilder<T> = Widget Function(BuildContext context, T data, Future<void> Function() refresh);

/// Loads one API resource and shows a skeleton, an error with retry, or
/// the content. Reloads quietly when any of [topics] changes elsewhere.
class RemoteView<T> extends StatefulWidget {
  const RemoteView({required this.load, required this.builder, this.skeleton, this.topics = const {}, super.key});

  final Future<T> Function() load;
  final RemoteBuilder<T> builder;
  final Widget? skeleton;
  final Set<Topic> topics;

  @override
  State<RemoteView<T>> createState() => RemoteViewState<T>();
}

class RemoteViewState<T> extends State<RemoteView<T>> {
  T? _data;
  Object? _error;
  bool _loading = true;
  RefreshBus? _bus;
  Map<Topic, int> _seen = const {};

  T? get data => _data;

  @override
  void initState() {
    super.initState();
    _load();
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
  void dispose() {
    _bus?.removeListener(_onBus);
    super.dispose();
  }

  void _onBus() {
    final bus = _bus;
    if (bus == null) {
      return;
    }
    final changed = widget.topics.any((topic) => bus.version(topic) != _seen[topic]);
    if (changed) {
      _seen = {for (final topic in widget.topics) topic: bus.version(topic)};
      reload();
    }
  }

  Future<void> _load() async {
    try {
      final result = await widget.load();
      if (mounted) {
        setState(() {
          _data = result;
          _error = null;
          _loading = false;
        });
      }
    } on Exception catch (error) {
      if (!mounted) {
        return;
      }
      if (_data != null) {
        setState(() => _loading = false);
        showError(context, error);
      } else {
        setState(() {
          _error = error;
          _loading = false;
        });
      }
    }
  }

  /// Reloads, keeping the current content on screen meanwhile.
  Future<void> reload() => _load();

  /// Replaces the content with a fresher copy an action returned.
  void replace(T value) => setState(() => _data = value);

  void _retry() {
    setState(() {
      _loading = true;
      _error = null;
    });
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final Widget child;
    if (_data != null) {
      child = widget.builder(context, _data as T, reload);
    } else if (_error != null) {
      child = ErrorState(error: _error!, onRetry: _retry);
    } else {
      child = widget.skeleton ?? const SkeletonList();
    }

    return AnimatedSwitcher(
      duration: const Duration(milliseconds: 260),
      switchInCurve: Curves.easeOutCubic,
      child: KeyedSubtree(key: ValueKey(_data != null ? 'data' : (_error != null ? 'error' : (_loading ? 'loading' : 'idle'))), child: child),
    );
  }
}

/// Pull-to-refresh with the brand colours.
class BrandRefresh extends StatelessWidget {
  const BrandRefresh({required this.onRefresh, required this.child, super.key});

  final Future<void> Function() onRefresh;
  final Widget child;

  @override
  Widget build(BuildContext context) => RefreshIndicator(color: NColors.wine, backgroundColor: Colors.white, onRefresh: onRefresh, child: child);
}
