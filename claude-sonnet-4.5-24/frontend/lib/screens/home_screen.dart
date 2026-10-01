import 'package:flutter/material.dart';
import 'package:graphql_flutter/graphql_flutter.dart';
import 'package:provider/provider.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';
import 'create_survey_screen.dart';
import 'survey_detail_screen.dart';

const String surveysQuery = r'''
  query GetSurveys {
    surveys {
      id
      title
      description
      createdAt
      isActive
    }
  }
''';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('FormFlow Survey Builder'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await Provider.of<AuthService>(context, listen: false).logout();
              if (context.mounted) {
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              }
            },
          ),
        ],
      ),
      body: Query(
        options: QueryOptions(
          document: gql(surveysQuery),
          pollInterval: const Duration(seconds: 10),
        ),
        builder: (QueryResult result, {refetch, fetchMore}) {
          if (result.hasException) {
            return Center(child: Text('Error: ${result.exception.toString()}'));
          }

          if (result.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final surveys = result.data?['surveys'] as List?;

          if (surveys == null || surveys.isEmpty) {
            return const Center(
              child: Text('No surveys yet. Create your first survey!'),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: surveys.length,
            itemBuilder: (context, index) {
              final survey = surveys[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  title: Text(survey['title'] ?? 'Untitled'),
                  subtitle: Text(survey['description'] ?? 'No description'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => SurveyDetailScreen(
                          surveyId: survey['id'],
                          surveyTitle: survey['title'] ?? 'Untitled',
                        ),
                      ),
                    );
                  },
                ),
              );
            },
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const CreateSurveyScreen()),
          );
        },
        icon: const Icon(Icons.add),
        label: const Text('New Survey'),
      ),
    );
  }
}